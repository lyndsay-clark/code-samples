var EMAIL_PATTERN = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
var PASS_MIN = 6;
var PASS_MAX = 75;

function configureForm(id, p) {
    $(id).on('submit', function(e) {
		var elem = $(this);
		e.preventDefault();
		var actionURL = HOST + $(this).attr('action');
		var dataStr = p["modifyData"] ? p["modifyData"]($(this), $(this).serialize()) : $(this).serialize();
		var msg = p["validation"] ? p["validation"]($(this)) : "";
		var msgDiv = p["msgDiv"] ? p["msgDiv"] : $('#alert-msg');
		var type = p["type"] ? p["type"] : "POST";
		
		if(p["sMsgDiv"]) msgDivId = elem.children(msgDivId);
		
		if (!p["beforeSend"]) {
           		p["beforeSend"] = function(ajax) {
            			$("loading-overlay").show();
           		}
		}
        
		if (!msg) {
			var ajax = $.ajax({
				beforeSend: p["beforeSend"](ajax),
				type: type,
				url:  actionURL,
				data: dataStr
			});
			ajax.success(function(res) {
				clearPin();
				if(p["successOverride"]) {
					p["success"](res);
				}
				else {
					if(!$.trim(res)) { /* res contains newlines */
					
						if(typeof p["success"] != 'undefined') {
							p["success"](elem);
						}
					}
					else msgDivId.html(res);
				}
				
			});
			ajax.error(function(res) {
				clearPin();
				$.mobile.loading("hide");
				msgDivId.html("We're sorry. Something went wrong. <br/> Please try again." + JSON.stringify(res));
			});
		} else clearPin();
		msgDivId.html(msg);
		stopLoading();
		return false;
	});
}

function clearPin() {
	$("input[name=pin]").val("");
}

