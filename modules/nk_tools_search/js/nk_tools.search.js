/**
 * @file
 * Custom method for handling 'Nk tools search input" element
 */

(function ($, Drupal, drupalSettings, debounce) {  

  Drupal.behaviors.nkToolsSearchToggle = {
   
    attach: function(context, settings) { 

      var self = this;

      var nk_tools_search_inputs = settings.nk_tools_search ? settings.nk_tools_search : null;
      var nk_tools = settings.nk_tools ? settings.nk_tools : {};

      if (nk_tools_search_inputs) {

        $.each(nk_tools_search_inputs, function(inputType, nk_tools_search) {

          var config = nk_tools_search.config || {};
         
          switch (inputType) {
          
            case 'search-inline':
              
              if (config.inline_target) {
           
                var parentInline = $('.search-inline-wrapper');
                // Search playlist handler
                if (parentInline.length) {
                  parentInline.once('rtpSearchPlaylist').each(function(si, searchBlock) {
                    var input = $(this).find('input[type="search"]');
                    if (input.length && input.data('target')) {

                      var icon =  input.next().find('i');

                      self.searchInline(input, $('.' + input.data('target')), '.rtp-brightcove-playlist-list', 500);
        
                      self.processIcon(input, icon, true);
                    
                    }
                  });
                }
              }
            break;

            case 'search':

            break;
 
            // This is a View related search input
            default: 

              var viewPath = nk_tools_search.view_path ? nk_tools_search.view_path : null;
              var queryName = nk_tools_search.view_filter || null;
              var config = nk_tools_search.config || {};

              if (queryName && viewPath && nk_tools_search.target) {
              
                $(context).find(nk_tools_search.target).once('searchPageLand').each(function() { 

                  var input = $(this);
                  var icon =  input.next().find('i');
                  var alterIcon = icon.data('icon-alter') ? icon.data('icon-alter') : 'forward';

                  // We are on the Search URL with ?query= included 
                  if (settings.path.currentQuery && settings.path.currentQuery[queryName]) {
              
                    if (nk_tools.layout && nk_tools.layout.hidden_class && icon.length && config.collapsed) {
                      input.toggleClass(nk_tools.layout.hidden_class);  
                    }

                    input.val(settings.path.currentQuery[queryName]); 
                    input.trigger('focus');
                    input.toggleClass('active');

                    if (icon.length) {
                      icon.text(alterIcon); 
                    }  
                  }  
                  
                  // Deal with enter 
                  input.on('keydown', function(e, ui) {
                    if (e.keyCode === 13) {
                      
                      e.preventDefault();
                      
                      console.log($(e.currentTarget));

                      if ($(e.currentTarget).val()) {
                        window.location.href = viewPath + '/?' + queryName + '=' + $(e.currentTarget).val(); 
                      }
                    }
                  });
                  
                  self.processIcon(input, icon);
                  

                });


                $(context).find('.toggle-search').once('searchToggle').each(function() {
                  var toggle = $(this); 
                  var input = toggle.parent().find('input:first');
                  if (input.length) {
                    var icon = toggle.find('i');
                    toggle.on('click', function(i, e) {
                      self.toggleCallback($(e), input, icon, viewPath, queryName, nk_tools, nk_tools_search);
                      return false; 
                    });
                  }
                });    
              
              }

            break;

          }

 
          /*
          var viewPath = nk_tools_search.view_path ? nk_tools_search.view_path : null;
          var queryName = nk_tools_search.view_filter || null;

            if (nk_tools_search.target) {
        
              
             
  
                $(context).find(nk_tools_search.target).once('searchPageLand').each(function() {   

                  // Icon definitions
                  var icon =  $(this).next().find('i');
              
                  var originalIcon = icon.length && icon.text() ? icon.text() : 'search';
                  var alterIcon = icon.length && icon.data('icon-alter') ? icon.data('icon-alter') : 'forward';

                  if (settings.path.currentQuery && settings.path.currentQuery[queryName]) {
              
                    if (nk_tools.layout && nk_tools.layout.hidden_class && icon.length) {
                      $(this).toggleClass(nk_tools.layout.hidden_class);  
                    }

                    $(this).val(settings.path.currentQuery[queryName]); 
                    $(this).trigger('focus');
                    $(this).toggleClass('active');

                    if (icon.length) {
                      $(this).next().find('i').text(alterIcon); 
                    }  
                  }  

                  if (icon.length) {
                    $(this).on('keydown', function(e, ui) {
                      // Drupal throbber: /core/themes/stable/images/core/throbber-inactive.png
                      if (e.keyCode === 13) {
                        e.preventDefault();
                        if ($(this).val()) {
                          window.location.href = viewPath + '/?' + queryName + '=' + $(this).val(); 
                        }
                      }
                    });

                   $(this).on('change keyup paste blur focus', function(e, ui) {
                
                     $(this).toggleClass('active');

                     // Take care of the icon
                     if ($(this).val()) {
                       icon.text(alterIcon).addClass('animated').addClass('bounceInRight'); 
                     }
                     else {
                       icon.text(originalIcon).removeClass('bounceInRight'); 
                     }
                  });
                }
              }); 
            //}
          //}
     
          $(context).find('.toggle-search').once('searchToggle').each(function() {
            var $this = $(this); 
            var input = $this.parent().find('input:first');
            if (input.length) {

              var dataTarget = input.data('target');
              if (!dataTarget) {
                $this.on('click', function(i, e) {
                  self.toggleCallback($(e), input, nk_tools, nk_tools_search);
                  return false; 
                });
              }
            }         
*/


/*
            $(this).on('click', function() {

              //var actions = $(this).parent().next();
              var input = $(this).parent().find('input:first');

              // Icon definitions
              var icon =  $(this).next().find('i');
              var originalIcon = icon.length && icon.text() ? icon.text() : 'search';
              var alterIcon = icon.length && icon.data('icon-alter') ? icon.data('icon-alter') : 'arrow_forward';
           
              //$(this).toggleClass('active');
 
              if (input.length) {
               
                if (input.is(':hidden')) {
              
                  // Show search input
                  if (nk_tools.layout && nk_tools.layout.hidden_class && input.hasClass(nk_tools.layout.hidden_class)) {
                    input.removeClass(nk_tools.layout.hidden_class);      
                  }

                  icon.text(originalIcon).removeClass('bounceInRight'); 
                  input.focus();
            
                  $(document).trigger('special.searchInput', [{ icon: icon, input: input, op: 'open'}]);
                }
                else {
                
                  // If the field was opened and there is a value (search term) entered go to a search page           
                  if (input.val() && queryName) {
                    //actions.find('.form-submit').trigger('click');
                    // Facets: /search?filters[0]=content_type:people
                    if (alterIcon !== 'close') {
                      window.location.href = viewPath + '/?' + queryName + '=' + input.val();
                    }
                    else {
                      input.val('');
                      icon.text(originalIcon).removeClass('bounceInRight');
                    }
                  }
                  else {

                    $(document).trigger('special.searchInput', [{ icon: icon, input: input, op: 'close'}]);

                    icon.text(originalIcon).removeClass('bounceInRight'); 

                    if (nk_tools.layout && nk_tools.layout.hidden_class) {
                      input.addClass(nk_tools.layout.hidden_class); 
                    }
                  } 
                }
          
              }

              return false;
        
            });
*/
        
          });

         }

/*         }); */

  //     }

    },

    processIcon: function(input, icon, clear) {
    
      if (icon.length) {

        var originalIcon = icon.text() ? icon.text() : 'search';
        var alterIcon = icon.data('icon-alter') ? icon.data('icon-alter') : 'forward';
        var iconAnimation = icon.data('in') ? icon.data('in') : 'bounce';
                    
        input.on('change keyup paste blur focus', function(e, ui) {
          // Set active class to search input 
          $(this).toggleClass('active');

          // Take care of the icon
          if ($(this).val()) {
            icon.text(alterIcon).addClass(iconAnimation); 
          }
          else {
            icon.text(originalIcon).removeClass(iconAnimation); 
          }
        });

        if (clear) {
          icon.on('click', function(i, e) {
            input.val('').trigger('keyup');
            icon.text(originalIcon).removeClass(iconAnimation);
          });
        }

      }
    },

    toggleCallback: function(toggle, input, icon, viewPath, queryName, nk_tools, nk_tools_search) {
    
     // var viewPath = nk_tools_search.view_path ? nk_tools_search.view_path : null;
     // var queryName = nk_tools_search.view_filter || null;
      var config = nk_tools_search.config || {};

      // Icon definitions
     // var icon =  toggle.next().find('i').length ? toggle.next().find('i') : [];
      var originalIcon = icon.length && icon.text() ? icon.text() : 'search';
      var alterIcon = icon.length && icon.data('icon-alter') ? icon.data('icon-alter') : 'arrow_forward';         
      var iconAnimation = icon.length && icon.data('in') ? icon.data('in') : 'bounce';

      if (input.is(':hidden') && config.collapsed) {
        // Show search input
        if (nk_tools.layout && nk_tools.layout.hidden_class && input.hasClass(nk_tools.layout.hidden_class)) {
          input.removeClass(nk_tools.layout.hidden_class);      
        }

        if (icon.length) {
          icon.text(originalIcon).removeClass(iconAnimation); 
        }
        input.focus();
            
        $(document).trigger('special.searchInput', [{ icon: icon, input: input, op: 'open'}]);
       
      }
      else {
                
        // If the field was opened and there is a value (search term) entered go to a search page           
        if (input.val() && queryName) {
          //actions.find('.form-submit').trigger('click');
          // Facets: /search?filters[0]=content_type:people
          if (viewPath) {
            window.location.href = viewPath + '/?' + queryName + '=' + input.val();
          }
          else {
            input.val('');

            if (icon.length) {
              icon.text(originalIcon).removeClass(iconAnimation);
            }
          }
        }
        else {

          $(document).trigger('special.searchInput', [{ icon: icon, input: input, op: 'close'}]);

          if (icon.length) {
            icon.text(originalIcon).removeClass(iconAnimation); 
          }
 
          if (config.collapsed && nk_tools.layout && nk_tools.layout.hidden_class) {
            input.addClass(nk_tools.layout.hidden_class); 
          }
        } 
      }
    },    

  
    searchInline: function(searchInput, parent, linksList, delay) {
  
      var self = this;
      var triggers = {
        self: null,
        links: [],
        parents: [],
        siblings: [],
        hiddenItems: {} 
      };

      // First generate usable object with all the data-relations
      parent.once('parentCheck').each(function() {
        $(this).find(linksList).each(function(parentIndex, list) { 
          var items = $(list).children();
          items.each(function(i, l) {
            if ($(this).hasClass('playlist-item')) {
              $(this).siblings().each(function(s, sibling) {
                var siblingLink = $(sibling).find('.bc-play');
                if (siblingLink.length) {
                  triggers.siblings.push(siblingLink);
                }
              });
              
              var link = $(this).find('.bc-play');
              if (link.length) {
                triggers.parents.push($(l));
                triggers.links.push(link);//  triggers.links[n] = $(this).find('a');
              }            
            }
          });
        });     
      });

      var debounceAll = debounce(function(input, query, parent, triggers) {  
      
        // Make sure to show all of the collapsible panes, if some was collapsed - open it
        parent.once('toggleOpen').each(function() {
          var toggle = $(this).prev().hasClass('collapsible-toggle') && !$(this).prev().hasClass('expanded') ? $(this).prev() : null;
          if (toggle) {
            toggle.addClass('expanded').trigger('click');
          }
        });

        if (!query) {
         // And we use "topPArent" parent in order to expand any other possible collapsible panes withi the whole markup
         var topParent = parent.parent();
         var subPanes = topParent.find('.collapsible-toggle');
         if (subPanes.length) {
           subPanes.once('subPanesToggle').each(function() {
              $(this).removeClass('expanded');
              $(this).find('.collapsible-content').each(function() {
                $(this).removeClass('expanded').attr('style', 'display: none');
              });
            }); 
          } 
        }

        var animationIn = input.data('in') ? input.data('in') : 'fadeIn';
        var animationOut = input.data('out') ? input.data('out') : 'fadeOut';
        
        $.each(triggers.links, function(index, link) {
          if (link.length && link.html().toUpperCase().indexOf(query) > -1) {
            triggers.parents[index].removeClass(animationOut).addClass(animationIn);
          }
          // Hide previously revealed items that do not match with current intput string
          else {
           triggers.parents[index].removeClass(animationIn).addClass(animationOut);
          }
        });
       
         
        var debounceResults = debounce(function() {
          $.each(triggers.links, function(index, link) {
            if (link.length && link.html().toUpperCase().indexOf(query) > -1) {
              triggers.parents[index].removeClass('hidden');
            }
            // Input value does not match any of items
            else {
             triggers.parents[index].addClass('hidden');
            }
          });

        }, 1);

        debounceResults();

      }, delay);

      searchInput.on('keyup', function(event) {
        var input = $(event.target);

        

        // A value being typed in the search field
        var query = input.val().toUpperCase();

        // When input value is being deleted - change back to "search" icon
        if (!query) {
        }

        debounceAll(input, query, parent, triggers);
      });
   
    }



  };

})(jQuery, Drupal, drupalSettings, Drupal.debounce);