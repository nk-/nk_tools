(function ($, Drupal, drupalSettings, debounce) {

  'use strict';
  
  // Define ajax commands object
  Drupal.AjaxCommands = Drupal.AjaxCommands || {};
  
  // Define our main factory
  Drupal.nkToolsFactory = Drupal.nkToolsFactory || {};
  
  Drupal.behaviors.nkToolsAjaxViews = {
    
    attach: function(context, settings) {
      
      var self = this;
      
      if (Drupal.AjaxCommands.prototype) { // Override default View's commands OR define new ones @see \Drupal\nk_tools\src\EventSubscriber\AjaxResponseSubscriber
      
        Drupal.AjaxCommands.prototype.nkToolsAjaxArguments = function(ajax, response) { 
          
          var href;
          var currentPath = response.view_path ? response.view_path : drupalSettings.path.baseUrl + drupalSettings.path.currentPath; 

          var filters;
          if (response.view_filters) {
            filters = $.param(response.view_filters);
          }

          if (response.view_args && Drupal.nkToolsFactory.isJson(response.view_args)) {

            var args = response.view_args.join('/');
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
            Drupal.nkToolsFactory.setAsyncUrl(href);
          }

        }
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
        Drupal.attachBehaviors(view.$view[0]);

        // Emit a custom event now so further processing can happen anywhere with a listener
        $(document).trigger('asyncView', {event: event, block: block, loaded: false, comands: comands, statusString: statusString, ajaxObject: ajaxObject, view: view});
      });

    },

    matchExistingViewDom: function(existing, object) {
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