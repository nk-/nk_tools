/**
 * @file
 * A set of useful methods to be used sitewide
 */

(function ($, Drupal, drupalSettings, debounce) {

  'use strict';

  Drupal.nkToolsFactory =  Drupal.nkToolsFactory || {};

  Drupal.behaviors.nkToolsFactoryLayout = {
    
    attach: function (context, settings) {

      var self = this;
      var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;


      var lastScrollTop = 0;
   
/*
      // Sticky elements vs drupal admin toolbar
      var adminToolbar = $('body').find('#toolbar-administration'); 
      if (adminToolbar.length) {
       // $(window).on('load', function() {
          self.stickyElements(adminToolbar, 'stickyInit');
     //   });
      }
*/

      // Progress element
      var progress = $('progress');    
      var progressValue;
      self.progressBar.config(progress);
        
      $(document).once('initLazy').on('lazyloaded', function(event) {   
         self.progressBar.config(progress);
      });
 

      // ALL calls to window.scroll event here
      $(document).on('scroll', function(event) {

        if ($(event.currentTarget).scrollTop() > 156 || $('body').scrollTop() > 156) {
   
        //if ($(event.currentTarget).scrollTop() > lastScrollTop) { // Scrolling down
          $('#page').addClass('sticky');
        }
        else { // Scrolling up
          //if ($(event.currentTarget).scrollTop() < 156) {
            $('#page').removeClass('sticky');
         // }
        }
  
        //lastScrollTop = $(event.currentTarget).scrollTop();
  
        // Progress element
        self.progressBar.progress(progress, progressValue);

        // Highlight blue text
        $('.text-highlight').each(function() {
          var thisTop = $(this).offset().top - parseFloat($(this).css('marginTop').replace(/auto/, 0));
          var offsetHighlight = 500;
          if ($(event.currentTarget).scrollTop() >= parseFloat(thisTop - offsetHighlight)) { 
            $(this).addClass('highlighted');
          }
        });

        // Process animated subtitles items
        var elements = $('.' +  Drupal.nkToolsFactory.processDeffered.data.element);
        if (elements.length) {
          elements.each(function(index, element) {
            if (index == (Drupal.nkToolsFactory.processDeffered.data.index + 1)) {
              var currentIndex = Drupal.nkToolsFactory.processDeffered.data.index;
              var parent = $(element).parent('.' + Drupal.nkToolsFactory.processDeffered.data.parent);
              var top = $(elements[currentIndex]).offset().top + $(elements[currentIndex]).height(); 
              if (top && window.pageYOffset > (top - 354)) { 
                Drupal.nkToolsFactory.processDeffered.data.index = event.index = index;
                Drupal.nkToolsFactory.processDeffered.show(event);
              } 
            }           
          });
        }
      
      });
    },
 
/*
   stickyElements: function(adminToolbar, onceCallback, originalTop) {
     var self = this;
     $('.sticky-element').once(onceCallback).each(function(i, element) {
       //if ($(element).css('top') || originalTop) {
         var currentTop = $(element).css('top');
         var elementTop = currentTop.replace('px', '').replace('%', '').replace('rem', '').replace('em', '');
         
         if (elementTop === '0') {
           return;
         }
         
         var elementTopValue;
         if (originalTop) {
           elementTopValue = originalTop;
         }
         else {
           if ($('body').hasClass('toolbar-tray-open')) {
             if ($('body').hasClass('toolbar-horizontal')) {
               elementTopValue = 94;
             }
             else if ($('body').hasClass('toolbar-vertical')) {
               elementTopValue = 55;
             }
           }
           else {
             elementTopValue = 46;
           } 
         }
        
        

         var calc = 'calc(' + currentTop + ' + ' + elementTopValue + 'px)';
         $(element).css('top', calc);
        
         adminToolbar.find('a.toolbar-item.trigger').once('toolbarClick').each(function() {
           $(this).on('click', function(e) {
             var newValue;
             var operator = ' + ';
             if ($('body').hasClass('toolbar-tray-open')) {
               
               if ($('body').hasClass('toolbar-horizontal')) {
                 elementTopValue = 44;
                 //operator = ' - ';
               }
               else if ($('body').hasClass('toolbar-vertical')) {
                 elementTopValue = 55;
                 //operator = ' - ';
               }
               // else {
               //  elementTopValue = 94;
               //  operator = ' - ';
               //  console.log(currentTop);
               // }
               //console.log(elementTopValue, currentTop);
               //var debounceTop = debounce(function() {
                 newValue = 'calc(' + currentTop + operator + elementTopValue + 'px)';
                 console.log(newValue);
                 $(element).css('top', newValue);
                 console.log($(element)); 
              // }, 1);
 
               //debounceTop(); 
  
 
             }
             else {
               if ($('body').hasClass('toolbar-horizontal')) {
                 elementTopValue = 94;
                 //console.log(elementTopValue, currentTop);
                 newValue = 'calc(' + currentTop + operator + elementTopValue + 'px)';
                 console.log(newValue);
                 $(element).css('top', newValue);
               }
             }

             
             
           });
         });
   
         adminToolbar.find('.toolbar-toggle-orientation button').once('toolbarOrientationClick').each(function() {  
           $(this).on('click', function(e) {
             calc = $(this).hasClass('toolbar-icon-toggle-vertical') ? 'calc(' + currentTop + ' + 39px)' : 'calc(' + currentTop + ' + 94px)';
             $(element).css('top', calc);
           });
         });
      // }
     });
   },
*/

   progressBar: {
     
     config: function(progress, target, offset = 0) {
       if (progress.length && progress.once('progressInit')) {
         
         // Set the max scrollable area
         var max;
         if (target && target.length) {
           offset = target.height() * 2;
           max = parseInt(target.offset().top + target.height() - offset); 
         }
         else {
           max = $(document).height() - $(window).height() + offset;
         } 
         progress.attr('max', max);
       }
     },

     progress: function(progress, value) {
       value = $(window).scrollTop() < 26 ? $(window).scrollTop() : $(window).scrollTop() + 25;
       //progress.attr('value', value);

       if ($(window).scrollTop() > 25) {
         progress.attr('value', value);
         progress.removeClass('bg-only');
       }
       else if ($(window).scrollTop() <= 26) {
         progress.addClass('bg-only');
       }
     }
   }
  };

})(jQuery, Drupal, drupalSettings, Drupal.debounce);