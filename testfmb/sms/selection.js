            
            var highlight_class = "success";
            var tableSelector = "table#recipientTable";
            var clickHandler = function(row){
                row.toggleClass(highlight_class);
                row.trigger("bgChange");
            }
            var getSelector = function(contactType, isIndian) {
                var nationalitySelector = "";
                if(isIndian){
                    nationalitySelector = strFormat("[data-is-indian='{}']", isIndian);
                }
                var selector = strFormat("{} tr{}.{}", 
                    tableSelector, nationalitySelector, contactType);
                return selector;
            }
            $(tableSelector).on('click', 'tr', function(){
                clickHandler($(this));
            });
            $(tableSelector).on('mouseenter', 'tr', function(evt){
                if(evt.ctrlKey){
                    clickHandler($(this));
                }
            });
            
            $("button.sel-all").click(function(){
                var contactType = $(this).attr("name");
                var isIndian = $(this).attr("data-is-indian");
                filerString = getSelector(contactType, isIndian)+":not(.success)";
                $.each($(filerString), function(){
                    clickHandler($(this));
                });
            });
            $("button.sel-none").click(function(){
                var contactType = $(this).attr("name");
                var isIndian = $(this).attr("data-is-indian");
                filerString = getSelector(contactType, isIndian)+".success";
                $.each($(filerString), function(){
                    clickHandler($(this));
                });
            });
            $("button.sel-toggle").click(function(){
                var contactType = $(this).attr("name");
                var isIndian = $(this).attr("data-is-indian");
                filerString = getSelector(contactType, isIndian);
                $.each($(filerString), function(){
                    clickHandler($(this));                    
                });
            }); 
            
            $(tableSelector).on("bgChange", "tr", function(){
                var selectedRecords = $('table#recipientTable tr.'+highlight_class);
                var len = selectedRecords.length;
                var selectionStatusString = "Selected "+len+" record(s).";
                $('#selection_status').html(selectionStatusString);
            });
            var getSelected = function(){
                selected = [];
                $.each($(strFormat("{} tbody:has(.{})", tableSelector, highlight_class)), function(){
                    studentRow = $(this).children(".student");
                    fatherRow = $(this).children(".father");
                    record = {};
                    $.each(studentRow.children(), function(){
                        record[$(this).attr("name")] = $(this).html();
                    });
                    record['CONTACT'] = $.map($(this).find("tr.success td[name='CONTACT']"), e => $(e).html());
                    selected.push(record);
                });
                
                //console.log(selected);
                return selected;
            }
