(function ($, Drupal, drupalSettings, debounce) {

  'use strict';
  
  // Define ajax commands object
  Drupal.AjaxCommands = Drupal.AjaxCommands || {};
  
  // Define our main factory
  Drupal.nkToolsFactory = Drupal.nkToolsFactory || {};
  
  Drupal.behaviors.nkToolsAjaxViews = {
    
    attach: function(context, settings) {
      
      var self = this;
      
       // Filters
       $('.nk-tools-ajax-filter').once('triggerAjaxFilter').each(function(i, trigger) {
         
         var widgetType;

         if ($(trigger).attr('type')) {
           widgetType = $(trigger).attr('type');
         }
         else {
           if ($(trigger).is('select')) {
             widgetType = 'select';
           }
         }
         
         var action; // = 'click';
         var delay = 750;

         switch (widgetType) {
/*
           case 'link':
             action = 'click';
             delay = 150;  
           break;
*/
           case 'select':
             action = 'change';
             delay = 150; 
           break; 
           default: 
             if ($(this).hasClass('form-autocomplete')) {
               action = 'autocompleteclose';
               delay = 150;
             }
             else {
               Drupal.nkToolsFactory.inputDelayed($(trigger));
               action = 'finishedinput';
               delay = 0;
             }
           break; 
         }
         
         $(trigger).once('triggerAjaxFilterAction').on(action, debounce(function(event) {
           var button = $(event.currentTarget).parents('form').find('.nk-tools-autotrigger input.form-submit');
           console.log(button);
           button.trigger('click');
             
           // Our custom ajax event
           $(document).once('nkToolsActiveElements').on('nkTools.activeElements', function(event, params) { 
             //params.appendBlock = data.append_block ? $(data.append_block) : {}
             self.resetFilter(event, params, button, 1);
           });

           }, delay)); 

       });
       
      if (Drupal.AjaxCommands.prototype) { // Override default View's commands OR define new ones @see \Drupal\nk_tools\src\EventSubscriber\AjaxResponseSubscriber
      
        Drupal.AjaxCommands.prototype.viewsScrollTop = function(ajax, response) {
          var offset = $(response.selector).offset();
          var scrollTarget = response.selector;
          while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
           scrollTarget = $(scrollTarget).parent();
          }

          if (offset && offset.top - 10 < $(scrollTarget).scrollTop()) {
            $(scrollTarget).animate({
              scrollTop: parseInt(offset.top) - 74 // This int is the only chaange, original is 10
            }, 900, 'swing', function (e) {});
          }
        };

        Drupal.AjaxCommands.prototype.nkToolsAjaxArguments = function(ajax, response) { 
          
          var href;
          var currentPath = response.view_path ? response.view_path : drupalSettings.path.baseUrl + drupalSettings.path.currentPath; 

          var filters;
          if (response.view_filters) {
            filters = $.param(response.view_filters);
          }
        
          var viewParams = {
            pager_element: 'mini', //$pager, //NULL,
            view_name: response.view_id,
            view_display_id: response.display_id,
            view_args: response.view_args,
            view_dom_id: response.view_dom_id
          };

          var currentArgs = self.getCurrentArgs(viewParams);
          
          var existingArgs;
          if (currentArgs.args && Drupal.nkToolsFactory.isJson(currentArgs.args)) {
            existingArgs = currentArgs.args;
          }  
          else {
            existingArgs = response.view_args && Drupal.nkToolsFactory.isJson(response.view_args) ? response.view_args : null;
          }
          
          if (existingArgs) {

            var args = existingArgs.join('/');

            if (filters) {
              href = response.view_pager ? currentPath + '/' + args + '?' + filters + '&page=' + response.view_pager : currentPath + '/' + args + '?' + filters;
            }
            else {
              href = response.view_pager ? currentPath + '/' + args + '?page=' + response.view_pager : currentPath + '/' + args;
            }
          }
          else {
            if (filters) {
              href = response.view_pager ? currentPath + '?' + filters + '&page=' + response.view_pager : currentPath + '?' + filters;
            }
            else {
              href = response.view_pager ? currentPath + '?page=' + response.view_pager : currentPath;
            }
          }
          
          // Update url
          if (href) {
            href = href.replace(/\/$/, ""); // Remove trailing slash
            if (href.substring(0, 1) !== '/') {
              href = '/' + href; // Add leading slas, essential for on/off states and url replacements
            }
            $(response.selector).once('setAsyncUrlOnce').each(function() {
              Drupal.nkToolsFactory.setAsyncUrl(href);
            });
          }
        };

/*
         Drupal.AjaxCommands.prototype.nkToolsAjax = function(ajax, response) { 
          if (ajax.event === 'finishedinput') {

            var elements = ajax.$form.find('.form-item input[type=text], .form-item input[type=email], .form-item select');
            if (elements.length) {
              $.each(elements, function(k, element) {
                
                if ($(element).val()) {   
                  $(element).trigger('focus');
console.log($(element));
                  var icon = $(element).parent().find('.select-toggle-default');
                  var closeIcon = $(element).parent().find('.select-toggle-close');
                  if (icon && icon.length) {
                    icon.toggleClass('hidden');
                  } 
                  if (closeIcon && closeIcon.length) {
                    closeIcon.toggleClass('hidden');

                    closeIcon.on('click', function(event) {
                      $(event.currentTarget).toggleClass('hidden');
                      var label = $(element).parent().find('label').length ? $(element).parent().find('label') : $(element).parent().prev('label');
                      $(element).attr('placeholder', label.text());
                      var type = element.type == 'select-one' || element.type === 'select' ? 'select' : element.type;
                      Drupal.nkToolsFactory.activeFormElements.resetElement(event, $(element), type, label, icon, closeIcon);
                    });
                  }
                }  
              }); 
            }
          }
        };
*/
      }
    },

    ajaxView: function(event, viewsSettings, block, args, progress, method) { 

      var self = this;
      var ajaxPath = viewsSettings.ajax_path;
      var viewData = block.view;

      if (args) {
        viewData.view_args = args; 
      }
      
      var queryString = Drupal.nkToolsFactory.processQuery(ajaxPath);
      
      if (viewData.filters) { //if ($(this).attr('data-filters')) {
        queryString += queryString.length ? '&' + viewData.filters : '?' + viewData.filters;
      }
 
      // Ajax action params
      var ajax_settings = {
        url: ajaxPath + queryString,
        submit: viewData,
        type: method ? method : 'POST',
        progress: progress ? progress : { type: 'fullscreen' }
      };

      Drupal.ajax(ajax_settings).execute().done(function(comands, statusString, ajaxObject) {
        // After having initialized the Leaflet Map and added features, allow other modules to get access to it via trigger
        //var view = $('.js-view-dom-id-' + viewData.view_dom_id); 
        var view = Drupal.views.instances['views_dom_id:' + viewData.view_dom_id];
        if (view) {
          
          Drupal.attachBehaviors(view.$view[0]);

          // Emit a custom event now so further processing can happen anywhere with a listener
          $(document).trigger('asyncView', {event: event, block: block, loaded: false, comands: comands, statusString: statusString, ajaxObject: ajaxObject, view: view});
        }
      });

    },

    resetFilter: function(event, params, button, click) {
      if (button.length && click) {
        button.trigger('click');
        $(document).trigger('nkTools.asyncView', {event: event, block: {}, loaded: true, widgetType: params.widgetType, element: params.element, op: params.op}); 
      }
    },

    matchExistingViewDom: function(existing, object) {
      console.log(existing.children().eq(0));
      if (existing && existing.length && existing.children().eq(0).length) {
        var existingClasses = existing.children().eq(0).attr('class').match(/(?:^|\s)js-view-dom-id-([^- ]+)(?:\s|$)/);
        if (existingClasses && existingClasses[1]) {
          object.view.view_dom_id = existingClasses[1];
        }
      }
    },

    getCurrentView: function(data) {
      var currentView;
      if (drupalSettings.views && drupalSettings.views.ajaxViews) {
        $.each(drupalSettings.views.ajaxViews, function(view_id, params) {
          if (data.view_id === params.view_name && data.display_id === params.view_display_id) {
            currentView = params;
          }  
        });
      }
      return currentView;
    },

    getCurrentArgs: function(view) {
      var self = this;
      var data = {
        'view_id': view.view_name,
        'display_id': view.view_display_id
      };

      var currentArgs = {
        data: data
      };
      var currentView = self.getCurrentView(data);
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
    },

    setViewArgument: function(value, data) {
      if (drupalSettings.views && drupalSettings.views.ajaxViews) {
        $.each(drupalSettings.views.ajaxViews, function(view_id, params) {
          if (data.view_id === params.view_name && data.display_id === params.view_display_id) {
            drupalSettings.views.ajaxViews[view_id].view_args = value;
          }  
        });
      }
    }
  
  };
   

})(jQuery, Drupal, drupalSettings, Drupal.debounce);