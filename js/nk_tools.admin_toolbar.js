/**
 * @file
 * A set of useful methods to be used along with Drupal's admin toolbar
 */

(function ($, Drupal, drupalSettings, debounce) {

  'use strict';

  Drupal.nkToolsFactory =  Drupal.nkToolsFactory || {};

  Drupal.behaviors.nkToolsAdmin = {
    
    attach: function (context, settings) {

      var self = this;
      // var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;

      // Sticky elements vs drupal admin toolbar
/*
      $(window).once('toolbarInit').on('load', debounce(function() {
        var adminToolbar = $('body').find('#toolbar-administration'); 
        if (adminToolbar.length) {
          self.stickyElements(adminToolbar, 'stickyInit');
        }
      }, 1)); 
*/
    },
 
    stickyElements: function(adminToolbar, onceCallback, originalTop) {
      var self = this;
      $('.sticky-element').once(onceCallback).each(function(i, element) {
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
                 elementTopValue = 48;
                 operator = '- ';
              }
              else if ($('body').hasClass('toolbar-vertical')) {
                 elementTopValue = 55;
              }
              newValue = 'calc(' + currentTop + operator + elementTopValue + 'px)';
              console.log(newValue);
              $(element).css('top', newValue);
            }
            else {
              if ($('body').hasClass('toolbar-horizontal')) {
                elementTopValue = 94;
                 newValue = 'calc(' + currentTop + operator + elementTopValue + 'px)';
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
      });
    }
  };

})(jQuery, Drupal, drupalSettings, Drupal.debounce);