/**
 * @file
 * A set of useful methods to be used sitewide
 */

(function ($, Drupal, drupalSettings, debounce) {

  'use strict';

  // Argument is passed from InvokeCommand
  $.fn.nkToolsViewSelectFilter = function(argument) {
  };

  /**
   * Trigger a callback when the selected images are loaded:
   * @param {Function} callback
   */
  $.fn.imgLoad = function(callback) {
    return this.each(function() {
      if (callback) {
        if (this.complete || /*for IE 10-*/ $(this).height() > 0) {
          callback.apply(this);
        }
        else {
          $(this).on('load', function() {
            callback.apply(this);
          });
        }
      }
    });
  };
  
  /*
  $.fn.getBgImage = function(callback) {
    var height = 0;
    var path = $(this).css('background-image').replace('url', '').replace('(', '').replace(')', '').replace('"', '').replace('"', '');
    var tempImg = $('<img />');
    tempImg.hide(); // Hide image
    tempImg.bind('load', callback);
    $('body').append(tempImg); // Add to DOM before </body>
    tempImg.attr('src', path);
    $('#tempImg').remove(); // Remove from DOM
  };
  */

  $.event.special.asyncView = {
    show: function(element) {
      return element;  
    },

    hide: function(element) {
      return element;
    }
  };

  // Custom "pip" event
/*
  $.event.special.pip = {
    
    show: function(element) {
      return element;  
    },

    hide: function(element) {
      return element;
    }
  };
*/
   $.event.special.bannerResize = {
    show: function(element) {
      return element;  
    },

    hide: function(element) {
      return element;
    }
  };



  // Custom event for nkTools search input element
  $.event.special.searchInput = {
    
    show: function(element) {
      return element;  
    },

    hide: function(element) { 
      return element;
    }
  };

  // Several main general features, always included and invoked 
  Drupal.behaviors.nkToolsFactory = {
    
    attach: function (context, settings) {
     
      var factory =  Drupal.nkToolsFactory || {};

      var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;

      factory.smoothScroll.clickCallback();

      factory.progressBar();

      
      // A magic - any link that has data-target attribute will work as a show/hide toggle
      // As long as value of the attribute is valid target's element id attribute 
      $(context).find('*[data-target]').once('dataTargetCall').each(function() {
        $(this).on('click', function(e) {
          
          var type = $(this).attr('type');
   
          // No use mainly, say this toggle can get this attribute programmatically so it does not trigger the logic anymore/further 
          var abort = $(this).data('abort');
          if (!abort) {
            
            if (type !== 'radio' && type !== 'checkbox') {
              e.preventDefault();
            }
            
            factory.dataTargetCallback($(this), context, settings);
          }
          //return false; 
        });
      });

      // "Vanishing" drupal messages
      /*
      $('.messages-wrapper').once('vanishMassage').each(function(i, m) {
        var debounceMessage = debounce(function() {
          $(m).fadeOut();
        }, 10000);
        debounceMessage();
      });
      */

      $(document).on('lazyloaded', function(event) {
        $('.lazy-placeholder').addClass('loaded');
        //event.index = factory.processSubtitles.data.index;
        //factory.processSubtitles.show(event); 
      });

      var debounceInit = debounce(function() {

        var event = { index: factory.processSubtitles.data.index };
        factory.processSubtitles.show(event);
 
        if (layout_settings && layout_settings.animate_class) {
          $('.underline-animated').addClass(layout_settings.animate_class);
        }
      }, 1);
 
      debounceInit();

      $(context).find('.text-highlight').each(function() {
        $(this).addClass('highlighted');
      });
    
      /*
      $(document).on('lazybeforeunveil', function(e) { 
      });
      */
   

      // Filters toggle widget
      var filtersToggle = $(context).find('.filters-toggle').once('filtersToggle');
      if (filtersToggle.length) {
        factory.toggleFilters(filtersToggle); 
      }

      // Tiles func/to-be widget
      $('.has-caption').once('tileHover').each(function() {
        factory.itemHover($(this)); 
      });

      var offsetTrigger = 280;
      var offsetTop = 0;
     
      $(window).on("scroll resize scrollstop", function(event) { 
        var elements = $('.' +  factory.processSubtitles.data.element);
        if (elements.length) {
          elements.each(function(index, element) {
            if (index == (factory.processSubtitles.data.index + 1)) {
              var currentIndex = factory.processSubtitles.data.index;
              var parent = $(element).parent('.' + factory.processSubtitles.data.parent);
              var top = $(elements[currentIndex]).offset().top + $(elements[currentIndex]).height(); 
              if (top && window.pageYOffset > (top - 354)) { 
                factory.processSubtitles.data.index = event.index = index;
                factory.processSubtitles.show(event);
              } 
            }           
          });
        }
 
        $('.bg-fixed').each(function() { 
          if ($('#footer-main').length) {
            var offFixedBlock = $('#footer-main').offset().top - 348; 
            if (window.pageYOffset > offFixedBlock) {
             $(this).removeClass('fixed');
            }
            else {
             factory.fixOnScroll.fix($(this), {top: offsetTop, trigger: offsetTrigger});
            }
          }
        });
      });
    }
  };

  Drupal.nkToolsFactory = {
 
    progressBar: function() {
      var progressBar = $('progress');

      if (progressBar.length) {
        var winHeight = $(window).height(); 
        var docHeight = $(document).height();
      
        var max, value;

        /* Set the max scrollable area */
        max = docHeight - winHeight;
        progressBar.attr('max', max);

        $(document).on('scroll', function() {
          value = $(window).scrollTop();
          progressBar.attr('value', value);
        });
      }
    },
    
    dataTargetCallback: function(trigger, context, settings) {
      var self = this;
      if (trigger.data('target')) {

        var target = $('#' + trigger.data('target'));

        if (target.length) {
         
          var sliding = trigger.data('sliding') ? trigger.data('sliding') : null;
          var parentAnimationOut = trigger.data('parent-out') ? trigger.data('parent-out') : null;
          var parentAnimationIn = trigger.data('parent-in') ? trigger.data('parent-in') : null;
          var animationOut = trigger.data('target-out') ? trigger.data('target-out') : null;
          var animationIn = trigger.data('target-in') ? trigger.data('target-in') : null;
          var bgChange = trigger.data('bg-active') ? trigger.data('bg-active') : null;
          var nkToolsLayout = settings.nk_tools && settings.nk_tools.layout ? settings.nk_tools.layout : null;
        
          // Do not give up on animations yet, sometimes animations definition is on target's attribute
          if (!animationIn) {
            animationIn = target.data('target-in') ? target.data('target-in') : null;
          } 

          if (!animationOut) {
            animationOut = target.data('target-out') ? target.data('target-out') : null; 
          }  

          if (parentAnimationOut) {
            trigger.parent().toggleClass(parentAnimationOut);
          }
          if (parentAnimationIn) {
            trigger.parent().toggleClass(parentAnimationIn);
          }
         
          var params = {
            siblings: target.siblings(),
            classes: {
              additionalClass: {
                name: nkToolsLayout && nkToolsLayout.hidden_class ? nkToolsLayout.hidden_class : 'hidden', 
                delay: 280
              }
            }
          };

          if (target.is(':visible')) {

            var multiple = trigger.data('target-multiple');

            if (!multiple) { // Only hide if the conntent of pane is not dynamic and more links load diverse inside (that is like "target-multiple" attribue for)
              params.classes.add = animationOut;
              params.classes.remove = animationIn;
              params.classes.additionalClass.callback = 'addClass'; 
              self.setClasses(target, params);

              if (sliding) {
                self.slidingItems(trigger, target, 'visible', nkToolsLayout.hidden_class);
              }
            }
          }
          else {
            if (sliding) {
              self.slidingItems(trigger, target, 'hidden', nkToolsLayout.hidden_class);
            }
            params.classes.add = animationIn;
            params.classes.remove = animationOut;
            params.classes.additionalClass.callback = 'removeClass'; 
            params.classes.additionalClass.delay = 1;
            self.setClasses(target, params);
          }
          
          if (!sliding) {
            var iconBack = trigger.find('.icon-back');
            if (iconBack.length) {
            
              iconBack.toggleClass(nkToolsLayout.hidden_class);
                  
              if (trigger.find('.toggle-icon').length) {
                trigger.find('.toggle-icon').toggleClass(nkToolsLayout.hidden_class);
              }
            } 
          }

          if (bgChange) {
            trigger.toggleClass(bgChange);
          }

          // Set some default classes toggle
          target.toggleClass('expanded'); 
          trigger.toggleClass('expanded');


          // Targets could containe a close toggle element with such class ".close-pane"
          var closeToggle = target.find('.close-pane');
          if (closeToggle.length) {
            closeToggle.each(function() {
              $(this).on('click', function(event) {
                params.classes.add = animationOut;
                params.classes.remove = animationIn;
                params.classes.additionalClass.callback = 'addClass'; 
                self.setClasses(target, params);
                return true;
              });
            });
          }
 
        }
      }
    },

    slidingItems: function(trigger, target, visibility, hiddenClass) {
      var parent = target.closest('.sliding-item');
      if (parent.length) {
        if (parent.siblings().length) {
          parent.siblings().each(function() {
            $(this).toggleClass(hiddenClass);   
          });
        }
      }
      var iconBack = trigger.find('.icon-back') || trigger;
      if (iconBack.length) {
        if (trigger.attr('role') === 'burger') {
          var top = $('#toolbar-administration').length ? $('#toolbar-administration').offset().top + $('#toolbar-administration').height() + 16 : 16;
          trigger.toggleClass('fixed').toggleClass('left-16').toggleClass('top-' + top);
        }
        iconBack.toggleClass(hiddenClass);
        if (trigger.find('.toggle-icon').length) {
          trigger.find('.toggle-icon').toggleClass(hiddenClass);
        }
      }
    },


    triggerToggleClass: function(element, target, targetClass, value) {
      if (targetClass) {
        if (value && element.val() === value) {
          target.removeClass(targetClass);
        }
        else {
          if (value) {
            target.addClass(targetClass);
          }
          else { // A single checkbox
            if (element[0].checked) {
              target.removeClass(targetClass);
            }
            else {
              target.addClass(targetClass);
            }
          }
        }
      }
    },    

    // A generic method for on/off classes. For example it works for animations in and out, show/hide and similar
    setClasses: function(targetObject, params) {
      if (targetObject.length && params.classes) {

        var nkToolsLayout = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;

        if (params.siblings) {
          params.siblings.each(function(index, sibling) {
            if (nkToolsLayout && nkToolsLayout.hidden_class && !$(sibling).hasClass(nkToolsLayout.hidden_class)) {
              if ($(sibling).data('icon') && $(sibling).data('icon') === 'close') {
                $(sibling).addClass(nkToolsLayout.hidden_class); 
              }
            }
          });
        }

        var targetId = targetObject.attr('id');
        if ($('#' + targetId + '-sibling').length && nkToolsLayout && nkToolsLayout.hidden_class) {
          $('#' + targetId + '-sibling').toggleClass(nkToolsLayout.hidden_class);
        }

        if (nkToolsLayout && nkToolsLayout.hidden_class && targetObject.hasClass(nkToolsLayout.hidden_class)) {
          targetObject.removeClass(nkToolsLayout.hidden_class); 
        } 

        if (params.classes.delete) {
          targetObject.remove();
        }
        else {
          if (params.classes.remove && params.classes.add) {
            targetObject.removeClass(params.classes.remove).addClass(params.classes.add);
          }
          else if (params.classes.remove && !params.classes.add) {
            targetObject.removeClass(params.classes.remove);
          }
          else if (!params.classes.remove && params.classes.add) {
            targetObject.addClass(params.classes.add);
          }
          else if (!params.classes.remove && !params.classes.add) {
            // If nothing of in& out classes set on the block - we still need to hide this overlay. Try adding a default nkTools "hidden" class
            // @see /admin/structure/nk-tools/settings
            if (nkToolsLayout && nkToolsLayout.hidden_class && !targetObject.hasClass(nkToolsLayout.hidden_class)) {
              targetObject.addClass(nkToolsLayout.hidden_class);                 
            }
          }
        }
        
        // If some additional class is in the game - take care of it here
        // It has debounce / delay so that we assure as much that animations run first  
        if (params.classes.additionalClass) { // && targetObject.hasClass(params.classes.additionalClass.name)) {
          if (params.classes.additionalClass.delay) {
            var debounceAdditional = debounce(function() {
              var callback = params.classes.additionalClass.callback;
              targetObject[callback](params.classes.additionalClass.name);   
            }, params.classes.additionalClass.delay);
       
            debounceAdditional();
          }
          else {
            if (params.classes.additionalClass && params.classes.additionalClass.callback) {
              var callback = params.classes.additionalClass.callback;
              targetObject[callback](params.classes.additionalClass.name); 
            } 
          } 
        } 
        
      }
    },
    
    itemHover: function(element) {

      var target;
      if (element.find(element.data('target')).length) {
        target = element.find(element.data('target'));
      }
      else {
        target =  element.next();
      } 

      if (target.length) {

        var animations = {
          animationIn: element.data('in'),
          animationOut: element.data('out')
        };
        
        element.on('mouseenter', debounce(function() {
        
          // If nothing of in& out classes set on the block - we still need to hide this overlay. Try adding a default nkTools "hidden" class
          // @see /admin/structure/nk-tools/settings
          var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;
          if (layout_settings && layout_settings.hidden_class && target.hasClass(layout_settings.hidden_class)) {
            target.removeClass(layout_settings.hidden_class);
          }
        
          if (animations.animationOut && target.hasClass(animations.animationOut)) {
            target.removeClass(animations.animationOut);
          }

          if (animations.animationIn && !target.hasClass(animations.animationIn)) {
            target.addClass(animations.animationIn);
          }

        }, 0))
          
        .on('mouseleave', debounce(function() {
          if (animations.animationIn && target.hasClass(animations.animationIn)) {
            target.removeClass(animations.animationIn);
          }
          if (animations.animationOut && !target.hasClass(animations.animationOut)) {
            target.addClass(animations.animationOut);
          }
          
        }, 1));
      }
      
    },
 
    processSubtitles: {
      data: {
        index: 0,
        element:'subtitles-animated',
        parent: 'has-subtitles' 
      },    
      show: function(event) {
        var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;
        $('.subtitles-animated').each(function(delta, element) {
          if (event.index == delta) {
            var animatedSubtitles = $(this).find('.' + layout_settings.animate_class);
            var last = animatedSubtitles.last();
            var first = animatedSubtitles.first();
            var fixedHeight = animatedSubtitles.length * last.height();

            $(element).css('height', fixedHeight + 'px');

            var debounceAnimation = [];

            animatedSubtitles.once('subtitlesAnimate').each(function(index, subtitle) {
        
              if ($(subtitle).length) {

                var animationIn = $(subtitle).data('in') || 'fadeIn';
                debounceAnimation[index] = debounce(function() {
                  if (layout_settings.hidden_class && $(subtitle).hasClass(layout_settings.hidden_class)) {
                    $(subtitle).removeClass(layout_settings.hidden_class);
                  }
                  var animationIn = $(subtitle).data('in') || 'fadeIn';
                  
                  $(subtitle).addClass(animationIn);
                  
                  if ($(subtitle).data('line')) {
                    $(subtitle).addClass($(subtitle).data('line'));  
                  }
                  //$(subtitle).removeClass(layout_settings.hidden_class).addClass(animationIn);  
                }, index * 750);
               
              }
           });
           
           $.each(debounceAnimation, function(i, animation) {
             animation();
           });

         }   
       });

      }
    },

    enterPictureInPicture: function(videoElement) {
      if (document.pictureInPictureEnabled && !videoElement.disablePictureInPicture) {
        try {
          if (document.pictureInPictureElement) {
            document.exitPictureInPicture();
          }
          videoElement.requestPictureInPicture();
        } catch(err) {
            console.error(err);
        }
      }
    },

    // Smooth scroll for internal links (anchors)
    smoothScroll: {
      
      fixedHeight: function(element) {
        var height = 24;
        if ($('body').hasClass('navbar-fixed')) {
          height += 64;
        }
        if ($('body').hasClass('toolbar-vertical')) {
          height += 40;
        }
        if ($('body').hasClass('toolbar-horizontal')) {
          height += 40;
          if ($('body').hasClass('toolbar-tray-open')) {
            height += 40;
          }
        }
        if (element && element.data('top-offset')) {
          height += element.data('top-offset');  
        }
        return height;
      },

      clickCallback: function() {
        
        var self = this;
        var anchors = $('a[href^="#"]:not([href="#"])');

        anchors.once('anchorClicked').each(function(index, anchor) {

          if (location.hash && $(location.hash).length) {
            
            var id = $(anchor).attr('href');
            var fixedHeight = self.fixedHeight($(this));
            
            if (id === location.hash) {
              $(window).on('load', debounce(function() {
                
                var parent = $(anchor).parents('li').length ? $(anchor).parents('li') : $(anchor).parent();

                parent.siblings().each(function(i, sibling) {
                  $(sibling).removeClass('active');
                });
          
                parent.toggleClass('active');


                $('html, body').once('scrollToArea').stop().animate({
                  'scrollTop': $(location.hash).offset().top // - fixedHeight
                }, 600, 'swing', function (e) {
                  if (history.pushState) {
                    history.pushState(null, null, location.hash);
                  }
                  else {
                    window.location.hash = location.hash;
                  }
                });
              }, 0));
            } 
          }

          $(this).on('click', function(e) { 
            e.preventDefault();
            
            var target = this.hash;

            if ($(target).length) {

              var fixedHeight = self.fixedHeight($(this));
              var parent = $(this).parents('li').length ? $(this).parents('li') : $(this).parent();

              parent.siblings().each(function(i, sibling) {
                $(sibling).removeClass('active');
              });
          
              parent.toggleClass('active');

              $('html, body').stop().animate({
                'scrollTop': $(target).offset().top - fixedHeight
              }, 600, 'swing', function (e) {
                if (history.pushState) {
                  history.pushState(null, null, target);
                }
                else {
                  window.location.hash = target;
                }
              });
            }
          });
        });

        var pathname = window.location.pathname;

        //$('a[href^="' + pathname + '#"]').on('click', function (e) {
        $('a[href*="#"]').on('click', function (e) {

          e.preventDefault();

          var target = this.hash;
          var $target = $(target);
          var fixedHeight = self.fixedHeight($(this));

          if ($target.length) {
            $(this).toggleClass('active');

            $('html, body').stop().animate({
              'scrollTop': $target.offset().top - fixedHeight
            }, 600, 'swing', function (e) {
              if (history.pushState) {
                history.pushState(null, null, target);
              }
              else {
                window.location.hash = target;
              }
            });
          }

          //return false; 

        });
      }
    },

    fixOnScroll: {

      onOff: function(element, fixedParams, animationParams, animate) {

        Drupal.nkToolsFactory.setClasses(element, fixedParams.params);

        if (animate !== 'none') {
          Drupal.nkToolsFactory.setClasses(element, animationParams);
        }
              
        if (fixedParams.miniBgImage) {
          element.css('background-image', fixedParams.miniBgImage); 
        }

        if (fixedParams.mainHeaderBottom) {
          element.css({ top: fixedParams.mainHeaderBottom});
        }
        
        if (fixedParams.top) {
          element.css('top', fixedParams.top);
        }
      },      
  
      fix: function(element, offsets) {

        var self = this;
        var animationIn = element.data('in') || 'slideInDown';
        var animationOut = element.data('out') || 'slideOutUp';
        var miniBg = element.data('bg') || null; 
        var mainHeader =  $('.fixed-header-marker').length ? '.fixed-header-marker' : element.data('header');  
        var miniBgImage;
        if (miniBg && $(miniBg).length) {
          miniBgImage = $(miniBg).css('background-image');
        }

        var mainHeaderBottom;
        if (mainHeader && $(mainHeader).length) {
          var offsetTop = $(mainHeader).data('top-offset') ? $(mainHeader).data('top-offset') / 2 : offsets.top; 
          mainHeaderBottom = $(mainHeader)[0].offsetTop + $(mainHeader)[0].offsetHeight + offsetTop;
        }  
          
        //$('.fixed-header-marker').length && 
        if (element.data('header')) {
          var header = element.data('header');
          $(header).find('.region').addClass('bg-white');
        }
          
        var offsetTrigger = element.data('top-offset') ? parseInt(element.data('top-offset')) : offsets.trigger;
        var fixedOffTarget = element.data('off-target') || null;
          
        // A version with "fixed off" trigger, i.e. we do not want it to overlap (z-index) on footer and we target content area/height only 
        if (fixedOffTarget && $(fixedOffTarget).length) {
            
          var lastChild = $(fixedOffTarget).children().last();
        
          if (window.pageYOffset > offsetTrigger) {

            var fixedParams = {
              params: window.pageYOffset < (lastChild.offset().top - lastChild.height()) ? {classes: {add: 'fixed'}} : {classes: {remove: 'fixed'}},
              miniBgImage: window.pageYOffset < (lastChild.offset().top - lastChild.height()) ? miniBgImage : 'none',
              mainHeaderBottom: window.pageYOffset < (lastChild.offset().top - lastChild.height()) ? mainHeaderBottom + 'px' : 'inherit'
            };

            var animationParams = window.pageYOffset < (lastChild.offset().top - lastChild.height()) ? {classes: {add: animationIn}} : {classes: {remove: animationIn}};

            if (window.pageYOffset < (lastChild.offset().top - lastChild.height())) {
              var debounceFix = debounce(function() {
                self.onOff(element, fixedParams, animationParams, animationIn);
              }, 1);
              debounceFix();
            }
            else {
              self.onOff(element, fixedParams, animationParams, animationIn);
            } 

             //self.onOff(element, fixedParams, animationParams, animationIn);
          }
          else if (window.pageYOffset < offsetTrigger) {

            var fixedParams = {
              params: {classes: {remove: 'fixed'}},
              miniBgImage: 'none',
              mainHeaderBottom: 'inherit'
            };

            var animationParams = {classes: {remove: animationIn}};

            self.onOff(element, fixedParams, animationParams, animationIn);
          }
        }   

        // Standard version without "off trigger"
        else {
           
          var fixedParams = {
            params: window.pageYOffset > offsetTrigger ? {classes: {add: 'fixed'}} : {classes: {remove: 'fixed'}},
            miniBgImage: window.pageYOffset > offsetTrigger ? miniBgImage : 'none',
            mainHeaderBottom: window.pageYOffset > offsetTrigger ? mainHeaderBottom + 'px' : 'inherit',
            debounce: window.pageYOffset > offsetTrigger ? true : false
          };

          var animationParams = window.pageYOffset > offsetTrigger ? {classes: {add: animationIn}} : {classes: {remove: animationIn}};

          if (window.pageYOffset > offsetTrigger) {
            var debounceFix = debounce(function() {
              self.onOff(element, fixedParams, animationParams, animationIn);
            }, 1);
            debounceFix(); 
          }
          else {
            self.onOff(element, fixedParams, animationParams, animationIn);
          } 
        }
    
      }
    },

    closeOverlay: function(trigger, params) { 
      var self = this;
      var targetObject = $(params.selector);

      if (params.event === 'clicked') { // When we want this instant applies
        self.setClasses(targetObject, params); 
        return;
      }
      else {
        trigger.on('click', function() { // Else it normally listens to a click event
          self.setClasses(targetObject, params);
          return false; 
        });
      } 
    },

    lazy: function() {
      //add simple support for background images:
    },
    
    toggleFilters: function(filtersToggle) {

      filtersToggle.each(function(index, toggle) {
         
        $(this).find('.filter-toggle').on('click', function() {
 
          var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;
 
          var label = $(this).find('#filters-label');
          if (label.length) {
            // If nothing of in& out classes set on the block - we still need to hide this overlay. Try adding a default nkTools "hidden" class
            // @see /admin/structure/nk-tools/settings
            if (layout_settings && layout_settings.hidden_class) {
              label.toggleClass(layout_settings.hidden_class);
            }
          }

          $('aside.first-xs').toggleClass('open');
  
          var target = $(this).data('target');
          if ($(target).length && layout_settings && layout_settings.desktop_only_class) {
            $(target).toggleClass(layout_settings.desktop_only_class); //.toggleClass('col-xs-9').toggleClass('col-xs-12');
          }
          return false;

        });
   
      });
    },

    ajaxView: function(event, viewsSettings, block, args, progress) { 

      var self = this;
      var ajaxPath = viewsSettings.ajax_path;
      var viewData = block.view;

      if (args) {
        viewData.view_args = args; 
      }
      
      var queryString = self.processQuery(ajaxPath);
      
      if (viewData.filters) { //if ($(this).attr('data-filters')) {
        queryString += queryString.length ? '&' + viewData.filters : '?' + viewData.filters;
      }
 
      // Ajax action params
      var ajax_settings = {
        url: ajaxPath + queryString,
        submit: viewData,
        progress: progress ? progress : { type: 'fullscreen' }
      };

      Drupal.ajax(ajax_settings).execute().done(function(comands, statusString, ajaxObject) {
        // After having initialized the Leaflet Map and added features, allow other modules to get access to it via trigger
        //var view = $('.js-view-dom-id-' + viewData.view_dom_id); 
        var view = Drupal.views.instances['views_dom_id:' + viewData.view_dom_id];
        Drupal.attachBehaviors(view.$view[0]);

        // Emit a custom event now so further processing can happen anywhere with a listener
        $(document).trigger('asyncView', {event: event, block: block, loaded: false, comands: comands, statusString: statusString, ajaxObject: ajaxObject, view: view});
      });

    },

    videoPip: function(wrapper, container, offset, op) {
      var self = this;
      
      var layout_settings = drupalSettings.nk_tools && drupalSettings.nk_tools.layout ? drupalSettings.nk_tools.layout : null;

      var prevScrollTop = 0;
      var offsetScroll = 5;
      var pipClose = wrapper.find('.pip-close');
             
      if (op === 'off') {
        pipClose.addClass('hidden');
               
        //container.addClass('fadeOut');
        // Emit this event now for other objects to have access 
        $(document).trigger('special.bannerResize', [{ parent: wrapper, container: container, op: 'hide'}]);
        container.removeClass('pip');
        return false;
      }

      if (container.length) {

        var i = 0;
        var currentScrollTop = window.pageYOffset;
        var currentIndex = i;
        
        //var top = container.offset().top + container.height();
        var top = container.parent().offset().top + container.parent().height();

        //var pipClose = container.find('.pip-close');
        //console.log(top, offset);

        if (top && window.pageYOffset > (top + offset) ) {  
          
           
            pipClose.on('click', function() {

              wrapper.removeClass('pip');

              $(this).addClass('hidden');
              // Emit this event now for other objects to have access 
              //$(document).trigger('special.bannerResize', [{ parent: wrapper, container: container, op: 'close'}]);
              //self.videoPip(wrapper, container, offset, 'off');
             
              return false; 
            });

            wrapper.on('mouseenter', function() {
              pipClose.removeClass('hidden');
            });

            wrapper.on('mouseleave', function() {
              pipClose.addClass('hidden');
            }); 

            // Emit this event now for other objects to have access 
            //$(document).trigger('special.bannerResize', [{ parent: wrapper, container: container, op: 'show'}]);

            container.addClass('pip');
 
            var debouncePipWinIn = debounce(function() {
              //var pipTop = 104;
              var pipTop = Math.abs(container.find('video').height() - 16); 
               pipClose.css('bottom', pipTop + 'px').removeClass('hidden'); 

            }, 1);
             
            debouncePipWinIn();
            

          }
          else {
               
            pipClose.addClass('hidden');
               
            // Emit this event now for other objects to have access 
            //$(document).trigger('special.bannerResize', [{ parent: wrapper, container: container, op: 'hide'}]);

            container.removeClass('pip');
          } 

        }
    },
    
    processQuery: function(path) {
      var queryString = window.location.search || '';
      if (queryString !== '') {
        queryString = queryString.slice(1).replace(/q=[^&]+&?|&?render=[^&]+/, '');
        if (queryString !== '') {
          queryString = (/\?/.test(path) ? '&' : '?') + queryString;
        }
      }
      return queryString;
    },
  
    isJson: function(input) {
      input = typeof input !== 'string' ? JSON.stringify(input) : input;

      try {
        input = JSON.parse(input);
      }
      catch (e) {
        console.log(e);
        return false;
      }

      if (typeof input === 'object' && input !== null) {
        return true;
      }

      return false;
    }

  };
 
})(jQuery, Drupal, drupalSettings, Drupal.debounce);