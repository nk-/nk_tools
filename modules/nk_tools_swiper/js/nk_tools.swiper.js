/**
 * @file
 * Init any instances of Swiper on the page.
 */

(function ($, Drupal, drupalSettings, debounce, Swiper) {

  'use strict';

   Drupal.nk_tools_swiper = Drupal.nk_tools_swiper || {};

   Drupal.nk_tools_swiper.swipers = {}; 

   Drupal.nk_tools_swiper.swiper = function(container, properties) {
   
    var options = {
      init: false,
    };
    
    options = properties ? $.extend(options, properties) : options;

    //$(window).once('swiperLoaded').on('load', function() {
      //if (Swiper !== undefined && Swiper != null) {
      //console.log(Swiper);
      return new Swiper(container, options);
   // });
     
  };

  Drupal.behaviors.nkToolsSwiper = {
  
    attach: function(context, settings) {
      var self = this;
     
      //$(window).on('load', function() {

      var nk_tools_swiper_settings = settings.nk_tools_swiper || null;

      if (nk_tools_swiper_settings && $.type(nk_tools_swiper_settings.swipers) !== 'undefined') { 
      var nk_tools = settings.nk_tools && settings.nk_tools.layout ? settings.nk_tools.layout : null;

      $.each(nk_tools_swiper_settings.swipers, function(id, swiper_settings) {

        var swiper_id = '#' + id;
        if ($(swiper_id).length) {
          $(swiper_id, context).once('swiper').each(function() {
            if (!swiper_settings.autoplay.delay) {
              swiper_settings.autoplay = false;
            }

            //var debounceSwiper = debounce(function() {
              if (Swiper !== null && $.type(Swiper) !== 'undefined') {
                Drupal.nk_tools_swiper.swipers[swiper_id] = new Swiper(swiper_id, swiper_settings);
                //Drupal.nk_tools_swiper.swipers[swiper_id].init();

                 Drupal.nk_tools_swiper.swipers[swiper_id].on('slideChangeTransitionEnd', function(swiper) {
                   var element = $(swiper.el);
                   $(element).find('.swiper-slide').each(function(i, slide) {
                     if (i == swiper.activeIndex) {
                       $(this).find('.banner-caption').each(function(d, child) {
                         if (nk_tools && nk_tools.hidden_class && $(child).hasClass(nk_tools.hidden_class)) {
                          $(child).removeClass(nk_tools.hidden_class);
                         }
                       });
                     }
                   });
                 }); 
               }

           // }, 200);
              
            //  debounceSwiper();

              /*
              console.log(Drupal.nk_tools_swiper.swipers[swiper_id]);

              var swiper_wrapper = $(swiper_id).find('.swiper-wrapper'); 
              if (swiper_wrapper.length) {
                swiper_wrapper.find('.swiper-slide').each(function(i, slide) {
                  $(slide).height(swiper_wrapper.height() - 8);
                });
              }
              */
  
              //self.processHeight(swiper_id);
 
               /*
              $(window).once('windowResized').on('load resize orientationchange', function() { 
                var debounceResize = debounce(function() {
                  self.processHeight(swiper_id);
                }, 0);
                debounceResize();
              });  
              */

              // A custom links (anywhere on the page) that trigger swiper slides 
              self.registerTriggers(Drupal.nk_tools_swiper.swipers[swiper_id], $(context).find('.swiper-trigger'), context, settings);       

             });
          }
        });

        }
      
     // });


      
    },

    processHeight: function(swiper_id) {
      var swiper_wrapper = $(swiper_id).find('.swiper-wrapper'); 
      if (swiper_wrapper.length) {
        var wrapperHeight = swiper_wrapper.height();
        swiper_wrapper.find('.swiper-slide').each(function(i, slide) {
          if (wrapperHeight > 8) {
            swiper_wrapper.height(wrapperHeight + 12);
            $(slide).height(wrapperHeight + 12);
          }
          else {
            $(slide).height(300);
          }
        });
      }
    },

    registerTriggers: function(swiper, triggers, context, settings) {
      triggers.once('swiperSwipe').each(function() {  
        $(this).on('click', function(e) {
       
          $(context).find('.swiper-trigger').each(function(i, sibling) {
            $(sibling).removeClass('active');
          });
       
          setTimeout(function() {
            $(e.currentTarget).addClass('active');
          }, 1);

          var index = $(this).attr('data-index') ? parseInt($(this).attr('data-index')) - 1 : 0;
          swiper.slideTo(index);
          return false;
        });
      });   
    }

  };

})(jQuery, Drupal, drupalSettings, Drupal.debounce, Swiper);