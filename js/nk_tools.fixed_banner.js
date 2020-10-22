(function ($, Drupal, drupalSettings, debounce) {
  
  'use strict';
  
/*
   $.event.special.bannerResize = {
    show: function(element) {
      return element;  
    },

    hide: function(element) {
      return element;
    }
  };
*/

  Drupal.behaviors.nk_toolsFixedBanner = {
    
    attach: function(context, settings) {

      var self = this;
      
      var bannerSettings = settings.nk_tools && settings.nk_tools.fixed_banners ? settings.nk_tools.fixed_banners : null;
      var layout = settings.nk_tools && settings.nk_tools.layout ? settings.nk_tools.layout : {};

      // Process banner container size on page load    
      if (bannerSettings) {
        $.each(bannerSettings, function(blockId, bannerSettings) {

          var imageUrl = bannerSettings.url;
          var bannerBlock = $('#' + blockId);
          var config = bannerSettings.config || {};

          if (imageUrl && bannerBlock.length) {

            // Some kind of a support for lazy load
            if (!config.css_bg && config.block_banner_lazy) {
 
              $(document).on('lazybeforeunveil', function() {

                bannerBlock.addClass('loaded');
                bannerBlock.addClass('lazy-placeholder');

                self.processBanner({type: 'init'}, bannerBlock, bannerSettings);
    
              });
            }
            // Standard option with banner as CSS background or image element without lazy loading option
            else { 

              // Wait for image loaded promise
              var placeholderImage = new Image();
              placeholderImage.src = imageUrl;
              $(placeholderImage).imgLoad(function() {
                // Process banner dimesnions and bg-image position
                self.processBanner({type: 'init'}, bannerBlock, bannerSettings, layout);
              });
            }

            // Trigger calculation om these events 
            $(window).once('bannerResize').on('resize orientationchange', function() { 
              var videoBlock = $(bannerSettings.config.video_block);
              if (!videoBlock.length || (videoBlock.length && !videoBlock.hasClass('ignore-resize'))) {
                self.processBanner({type: 'resize'}, bannerBlock, bannerSettings, layout); 
              }
            }); 

            // Usage of our custom event - external call (from some other flow/script)
            $(document).once('externalCall').on('special.bannerResize', debounce(function(event, data) {
             // console.log(data);
              if (data.op === 'close' || data.op === 'hide') {
                var videoBlock = $(bannerSettings.config.video_block);
                if (!videoBlock.length || (videoBlock.length && !videoBlock.hasClass('ignore-resize'))) {
                  self.processBanner({type: 'resize'}, bannerBlock, bannerSettings, layout); 
                } 
              }
            }, 1));

          }

        });
      }
    },

   
    setVideo: function(videoBlock, videoParams) { 
      if (!videoBlock.hasClass('pip') && !videoBlock.hasClass('ignore-resize')) {

        videoBlock.width(videoParams.currentWidth).height(videoParams.currentHeight);
        
        if (videoParams.top && !videoParams.bottom) {
          videoBlock.css({'top': videoParams.top + 'px', 'max-width': videoParams.maxWidth});
        }
        else if (!videoParams.top && videoParams.bottom) {
          videoBlock.css({'bottom':  videoParams.bottom + 'px', 'max-width': videoParams.maxWidth});
        }
      }
    },
    
    processCaption: function(bannerBlock, layout, offsetTop) { 
      var bannerCaption = bannerBlock.data('caption') || null;  
      if (bannerCaption && $(bannerCaption).length && layout.hidden_class && $(bannerCaption).hasClass(layout.hidden_class)) {
        var bottom = parseInt(offsetTop / 2) + 'px';
        $(bannerCaption).removeClass(layout.hidden_class).css({'margin-bottom': bottom});
      }
    },

    // A custom method to adjust banner image container's dimensions based on image data given from backend
    processBanner: function(event, bannerBlock, bannerSettings, layout) { 
    
      var self = this;
      
      var adminToolbar = $('body').hasClass('toolbar-fixed') && $('#toolbar-bar').length ? $('#toolbar-bar') : 0;
    
      //var blockWidth = bannerBlock[0].offsetWidth;
      
      var ratio = bannerSettings.ratio;
      
      var topConfig = bannerSettings.config.field_top_offset ? parseInt(bannerSettings.config.field_top_offset.value) : parseInt(bannerSettings.config.banner_offset);

      var cssTop = 0;

      var addAdmin  = adminToolbar && adminToolbar.length ? adminToolbar.height() : 0;

      var horizontalAdmin = addAdmin && $('body').hasClass('toolbar-horizontal');

      // This can be either DOM element with '.fixed-header-marker' class or banner's block config itself
      var markerElement;

      // Relevant element from whose bottom to start can be found either in template (as attribute) or else is on block setting
      if (bannerBlock.data('header')) {
        markerElement = $(bannerBlock.data('header'));
      }
      else {
        if (bannerSettings.config.banner_fixed_element) {
          markerElement = $(bannerSettings.config.banner_fixed_element);
        }
      }

      if (markerElement) {
        if (markerElement.data('top-offset')) {
          cssTop =  Math.abs(parseInt(markerElement.height() + markerElement.data('top-offset')) - topConfig - addAdmin);
        }
        else {
         cssTop =  horizontalAdmin ? Math.abs(markerElement.offset().top + markerElement.height() - topConfig - addAdmin) : Math.abs(markerElement.offset().top + markerElement.height() - topConfig);
        }
      }
      else {
        cssTop = topConfig;
      }

      var width;
      var videoTop, videoBottom;
      
      var videoOffset = bannerSettings.config.video_offset ? parseInt(bannerSettings.config.video_offset) : null;
      if (!videoOffset) {
        videoOffset = topConfig;
      }

      if (bannerSettings.config.banner_set_fixed_width && bannerSettings.config.banner_fixed_width && $(window).width() >= bannerSettings.config.banner_fixed_width) {
        width = parseInt(bannerSettings.config.banner_fixed_width);
        videoTop = '0px'; //cssTop + videoOffset;
      }
      else {
        width =  $(window).width();
        videoBottom = '0px';

      }
      
      var height = width / ratio;
   
      var currentHeight;

      // Take care of any video in the banner
      if (bannerSettings.config.video_block && bannerSettings.config.video_width) {

        var videoBlock = $(bannerSettings.config.video_block);
        var video = videoBlock.find('video');
        //var setHeight = video.is(':visible') ? video.height() : parseInt(bannerSettings.config.video_height); //height;
        var add = 0 ;//adminToolbar && adminToolbar.length ? addAdmin : 0;
 


        if (video.is(':visible')) {
          currentHeight =  parseFloat(video.height()); // + topConfig); 
        }
        else {
          currentHeight = parseFloat(height); // + topConfig); 
        }

        var videoParams = {
          cssTop: cssTop,
          //addAdmin: addAdmin,  
          currentHeight: currentHeight,
          currentWidth: '100%',
          top: videoTop,
          bottom: videoBottom,
          maxWidth: bannerSettings.config.video_width ? parseInt(bannerSettings.config.video_width) + 'px' : 'auto' 
        };

        self.setVideo(videoBlock, videoParams);

        var parent = bannerSettings.config.banner_fixed_element ? $(bannerSettings.config.banner_fixed_element) : bannerBlock.parent();
        //parent.width(width).height(currentHeight).css({'top': cssTop + 'px'});
        bannerBlock.width(width).height(currentHeight);
      
      }
      else {

        currentHeight = addAdmin ? parseFloat(height + cssTop - (parseFloat(topConfig + addAdmin))) - 23 : parseFloat(height - (topConfig / 2));

        var parent = bannerSettings.config.banner_fixed_element ? $(bannerSettings.config.banner_fixed_element) : bannerBlock.parent();
        var setTop = addAdmin ? parseFloat(topConfig + addAdmin) : topConfig;

        parent.css({'top': setTop + 'px'}); //.width(width).height(currentHeight).css({'top': cssTop + 'px'});

        bannerBlock.height(currentHeight); //.css({'top': cssTop + 'px'});
      }
            
      // Take care of any caption here
      self.processCaption(bannerBlock, layout, cssTop); 

    }  

  };

})(jQuery, Drupal, drupalSettings, Drupal.debounce);