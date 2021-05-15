/**
 * @file
 * "Async view" feature/block.
 */

(function ($, Drupal, drupalSettings) { 

  'use strict';

   Drupal.nkToolsFactory = Drupal.nkToolsFactory || {};
   Drupal.behaviors.nkToolsAjaxViews = Drupal.behaviors.nkToolsAjaxViews || {}

   Drupal.behaviors.nkToolsAsyncView = {
    
     attach: function(context, settings) {
       
       var self = this;
       var asyncBlocks = settings.nk_tools && settings.nk_tools.asyncBlocks;
 
       $.each(asyncBlocks, function(target, asyncBlock) {
         
         // Note double loop since each block can have multiple views/displays attached
         $.each(asyncBlock, function(delta, blockObject) {

           if (blockObject.trigger && blockObject.view) {

             // Process this block's logig
             //console.log(blockObject.use_rendered);
             //var existing = blockObject.use_rendered && blockObject.use_rendered[0] ? $(blockObject.use_rendered[0]) : null; // + ' .views-element-container');
             var existing = blockObject.use_rendered ? $(blockObject.use_rendered) : null;
             self.process(blockObject, existing);

             // A View container exists and it was just async loaded, a custom "special.asyncView" event  
             $(document).once('nkToolsAsyncView').on('special.asyncView', function(event, params) { 
               self.postProcess(event, params, blockObject, settings);
             });
           }
         });
       });
     },

     process: function(blockObject, existing) {

       var self = this;

       // Click (on) event on async view link trigger, specified on block config 
       $(blockObject.trigger).once('asyncView').each(function(i, trigger) { 

         // Add attribute to relate each trigger link with the view that it is calling
         // $(this).attr('data-target', blockObject.view.view_dom_id);

         $(this).on('click', function(event) {

           var iconClose = $(this).find('i') || $(this).next('i');
           var currentArgs = self.getCurrentArgs(blockObject.view);
      
           var setArgs = [];
           // First check in the DOM, this way we can override in Twig template and have unique arg for each of trigger links 
           if ($(this).attr('data-args').length && Drupal.nkToolsFactory.isJson($(this).attr('data-args'))) {
             setArgs = $.parseJSON($(this).attr('data-args'));
           }
           else {
             setArgs = $(this).attr('data-id') && $(this).attr('data-id') !== 'reset' ? [$(this).attr('data-id')] : [];
           }
             

           if ($(this).attr('data-view-id') && $(this).attr('data-display-id')) {
             blockObject.view.view_name = $(this).attr('data-view-id');
             blockObject.view.view_display_id = $(this).attr('data-display-id');
           }

           if ($(this).hasClass('current')) { // This is cancelling, clicking-off the button
           
             // Trigger custom event
             $(document).trigger('special.asyncView', {event: event, block: blockObject, loaded: true});

             // Take care of Views arguments
             var getCurrentArgs = self.getCurrentArgs(blockObject.view);
             var currentArgs = getCurrentArgs.args;
             var data = getCurrentArgs.data || {};

             // Parse and find actual view dom id 
             Drupal.behaviors.nkToolsAjaxViews.matchExistingViewDom(existing, blockObject);

             var order = $(this).attr('data-order') ? parseInt($(this).attr('data-order')) - 1 : 0;

             if (currentArgs && currentArgs[order]) {
               currentArgs[order] = 'all';
               setArgs = currentArgs;
             }

             Drupal.behaviors.nkToolsAjaxViews.setViewArgument(setArgs, data);

             if (setArgs.length) {
               setArgs = setArgs.join('/');
             }
 
             // Now invoke our main ajax view method
             Drupal.behaviors.nkToolsAjaxViews.ajaxView(event, drupalSettings.views, blockObject, setArgs, { type: 'fullscreen' }, 'POST');

             $(this).removeClass('current').removeClass('btn-active').trigger('blur');

            
             if (iconClose.length) {
               iconClose.addClass('hidden');
             } 

           }
           else {
               
             if ($(this).siblings().length) {
               $(this).siblings().each(function(index, sibling) {
                 var iconClose = $(sibling).find('i') || $(sibling).prev('i');
                 if (iconClose.length) {
                   iconClose.addClass('hidden');
                 }
                 $(sibling).removeClass('current').removeClass('btn-active');
               });
             }
 
             $(this).addClass('current').addClass('btn-active');

             if (iconClose.length) {
               iconClose.removeClass('hidden');
             }

             // Take care of Views arguments
             var getCurrentArgs = self.getCurrentArgs(blockObject.view);
             var currentArgs = getCurrentArgs.args;
             var data = getCurrentArgs.data || {};

             // Parse and find actual view dom id        
             Drupal.behaviors.nkToolsAjaxViews.matchExistingViewDom(existing, blockObject);
             
             // Override default settings with values that are set directly on html/twig         
             if ($(this).attr('data-view-id') && $(this).attr('data-display-id')) {
               
               blockObject.view.view_name = $(this).attr('data-view-id');
               blockObject.view.view_display_id = $(this).attr('data-display-id');

               var order = $(this).attr('data-order') ? parseInt($(this).attr('data-order')) - 1 : 0;

               if (setArgs.length > 1) {
                 var allIndex = setArgs.indexOf('all');
                 if (currentArgs && currentArgs[allIndex]) {
                   setArgs[allIndex] = currentArgs[allIndex];
                 }
                 setArgs = setArgs.join('/');
               }
               else {
                 if (currentArgs && currentArgs.length) {
                   currentArgs[order] = setArgs[order];
                   setArgs =  currentArgs.join('/');
                 }
                 else {
                   setArgs = setArgs[0];
                 }
               } 
               Drupal.behaviors.nkToolsAjaxViews.setViewArgument(setArgs, data);

             }

             // Now invoke our main ajax view method
             Drupal.behaviors.nkToolsAjaxViews.ajaxView(event, drupalSettings.views, blockObject, setArgs, { type: 'fullscreen' }, 'POST');

           }
           return false;
         });
       });
     
     },

     postProcess: function(event, params, blockObject, settings) {
     
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
     },

     getCurrentArgs: function(view) {
       var data = {
         'view_id': view.view_name,
         'display_id': view.view_display_id
       };

       var currentArgs = {
         data: data
       };
       var currentView = Drupal.behaviors.nkToolsAjaxViews.getCurrentView(data);
       var currentArgs = [];
       if (currentView && currentView.view_args && currentView.view_args !== '') {
         if (currentView.view_args.indexOf('/')) {
           currentArgs.args = currentView.view_args.split('/');
         }
         else {
           currentArgs.args[0] = currentView.view_args;
         }
       }
       return currentArgs;
     }


  };

})(jQuery, Drupal, drupalSettings);