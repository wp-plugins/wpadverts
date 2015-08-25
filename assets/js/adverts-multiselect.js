jQuery(function($) {
    
  if($(".adverts-multiselect").length == 0) {
      return;
  }

  $("select.adverts-multiselect").each(function(index, item) {
      var $this = $(item);
      var $parent = $this.parent();

      var holder = $("<div></div>");
      holder.addClass("adverts-multiselect-holder");

      var input = $('<input type="text" />');
      input.attr("id", $this.attr("name"));
      input.attr("id", $this.attr("id"));
      input.attr("placeholder", adverts_multiselect_lang.hint);
      input.attr("autocomplete", "off");
      input.addClass("adverts-multiselect-input");
      input.focus(function(e) {
            $(this).blur();
            $(this).addClass("adverts-multiselect-open");
            $(this).parent().find(".adverts-multiselect-options").css("width", $(this).outerWidth()-1);
            $(this).parent().find(".adverts-multiselect-options").show();
            e.stopPropagation();
      });      
      input.click(function(e) {
            $(this).blur();
            $(this).addClass("adverts-multiselect-open");
            $(this).parent().find(".adverts-multiselect-options").css("width", $(this).outerWidth()-1);
            $(this).parent().find(".adverts-multiselect-options").show();
            e.stopPropagation();
      });

      var options = $("<div></div>");
      options.addClass("adverts-multiselect-options");

      $this.find("option").each(function(i, o) {
          var o = $(o);
          var label = $("<label></label>");              
          label.attr("for", input.attr("id")+"-"+i);
          
          if(o.data("depth")) {
              label.css("padding-left", (parseInt(o.data("depth"))*20).toString() + "px" )
          }
          
          var checkbox = $('<input type="checkbox" />');
          checkbox.attr("id", input.attr("id")+"-"+i);
          checkbox.attr("value", o.attr("value"));
          checkbox.attr("name", $this.attr("name"));
          checkbox.data("wpjb-owner", input.attr("id"));
          checkbox.change(function() {
              var owner = $("#"+$(this).data("wpjb-owner"));
              var all = $(this).closest(".adverts-multiselect-options").find("input");
              var checked = [];

              all.each(function(j, c) {
                  if($(c).is(":checked")) {
                      checked.push($(c).parent().text().trim());
                  }
              });

              owner.attr("value", checked.join(", "));
          });
          if(o.is(":selected")) {
              checkbox.attr("checked", "checked");
          }

          label.append(checkbox).append(" ").append(o.text());
          options.append(label);
      });

      holder.append(input).append(options);

      $this.remove();
      $parent.append(holder)

      options.find("input[type=checkbox]").change();
  });

  $(document).mouseup(function(e) {
        var container = $(".adverts-multiselect-options");

        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            container.parent().find("input").removeClass("adverts-multiselect-open");
        }
  });

});