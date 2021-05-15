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


      // Sticky navbar
      var stickyNavbar = self.stickyNavbar($('#navbar')) || {};
      var lastScrollTop = 0;

      // Progress element
      var progress = $('progress');    
      var progressValue;
      self.progressBar.config(progress);
        
      $(document).once('initLazy').on('lazyloaded', function(event) {   
         self.progressBar.config(progress);
      });
 

      // ALL calls to window.scroll event here
      $(window).on('scroll', function(event) {
           
        if ($(event.currentTarget).scrollTop() > lastScrollTop) { // Scrolling down
          $('#page').addClass('sticky');
        }
        else { // Scrolling up
          if ($(event.currentTarget).scrollTop() < 156) {
            $('#page').removeClass('sticky');
          }
        }
  
        lastScrollTop = $(event.currentTarget).scrollTop();
  
        // Progress element
        self.progressBar.progress(progress, progressValue);
      
      });
   },

   stickyNavbar: function(navbar) {
     if (navbar.length) {
       return {
         top: navbar.offset().top,
         height: navbar.height(),
         offset: $('#toolbar-bar').length ? $('#toolbar-bar').height() * 2 : 0
       };
     }
   },

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