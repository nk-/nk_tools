/**
 * @file
 * "Async view" feature/block.
 */

(function ($, Drupal, drupalSettings) { 

  'use strict';

   Drupal.nkToolsFactory = Drupal.nkToolsFactory || {};

   Drupal.behaviors.nkToolsAsyncView = {
    
     attach: function(context, settings) {
       
       var self = this;
       var asyncBlocks = settings.nk_tools && settings.nk_tools.asyncBlocks;

       $.each(asyncBlocks, function(target, asyncBlock) {
         
         // Note double loop since each block can have multiple views/displays attached
         $.each(asyncBlock, function(delta, blockObject) {

           if (blockObject.trigger && blockObject.view) {
             
             var view = $('.js-view-dom-id-' + blockObject.view.view_dom_id); 
             var existing = blockObject.use_rendered && blockObject.use_rendered[0] ? $(context).find(blockObject.use_rendered[0]) : null; // + ' .views-element-container');

             if (view.length) {

               // Click (on) event on async view link trigger, specified on block config 
               $(context).find(blockObject.trigger).once('asyncView').each(function() { 

                 // Add attribute to relate each trigger link with the view that it is calling
                 $(this).attr('data-target', blockObject.view.view_dom_id);

                 $(this).on('click', function(event) {
                 
                   //blockObject.view.filters = $(this).attr('data-filters');
                 
                   if ($(this).hasClass('current')) {
                     $(document).trigger('nkTools.asyncView', {event: event, block: blockObject, loaded: true});
                   }
                   else {
                  
                     $(this).addClass('current').addClass('btn-active');
                  
                     if ($(this).siblings().length) {
                       $(this).siblings().each(function(index, sibling) {
                         $(sibling).removeClass('current').removeClass('btn-active');
                       });
                     }
 
                     // First check in the DOM, this way we can override in Twig template and have unique arg for each of trigger links 
                     var args = $(this).attr('data-id') && $(this).attr('data-id') !== 'reset' ? $(this).attr('data-id') : null;
                     if (!args) {
                       args = blockObject.view.view_args ? blockObject.view.view_args : null;
                     } 
                     
                     if (existing && existing.length) {
                        var existingClasses = existing.children().eq(0).attr('class').match(/(?:^|\s)js-view-dom-id-([^- ]+)(?:\s|$)/)[1];
                        blockObject.view.view_dom_id = existingClasses;
                     }
                     Drupal.nkToolsFactory.ajaxView(event, drupalSettings.views, blockObject, args);
                   }
                   return false;
                 });
               });

               // A View container exists and it was just async loaded, a custom "nkTools.asyncView" event  
               $(document).once('nkToolsAsyncView').on('nkTools.asyncView', function(event, params) { 

                 // Parent Drupal Block related manipulations
                 var currentBlock = params.block && params.block.block_id ? params.block.block_id : null;
                 
                 if (currentBlock === blockObject.block_id) {
                
                   var block = $('.' + currentBlock);

                   if (block.length) {
     
                     // First remove any hidden class (default configuration hidden class which does not exist on the block config itself)
                     // @see /admin/structure/nk-tools/settings
                     var layout_settings = settings.nk_tools && settings.nk_tools.layout ? settings.nk_tools.layout : null;
                     if (layout_settings && layout_settings.hidden_class && block.hasClass(layout_settings.hidden_class)) {
                       block.removeClass(layout_settings.hidden_class);                 
                     }
                     // Then check for main animation class (i.e. we may use animate.css with class "animated")
                     // @see /admin/structure/nk-tools/settings 
                     if (layout_settings && layout_settings.animate_class && !block.hasClass(layout_settings.animate_class)) {
                       block.addClass(layout_settings.animate_class);
                     }
                     
                     if (blockObject.animationOut && block.hasClass(blockObject.animationOut)) { 
                       block.removeClass(blockObject.animationOut);
                     }

                     if (blockObject.additionalClass && !block.hasClass(blockObject.additionalClass)) {
                       block.addClass(blockObject.additionalClass);
                     }

                     if (blockObject.animationIn && !block.hasClass(blockObject.animationIn)) {
                       block.addClass(blockObject.animationIn); 
                     }  
                                    
                     var trigger = block.find('.self-close');

                     if (trigger.length) {
                       var overlayParams = {
                         selector: '.' + blockObject.block_id,
                         classes: { remove: blockObject.animationIn, add: blockObject.animationOut }
                       };
                       Drupal.nkToolsFactory.closeOverlay(trigger, overlayParams); 
                    }
                  }
                }
              });
               
            }
          }

        });
 
      });
      
    }

  };

})(jQuery, Drupal, drupalSettings);