function uploader(holder, type, output, groupID) {
    var acceptedTypesArr = {};
	
	
    switch(type) {
        case "video":
            acceptedTypesArr = {
              "video/avi": true,
              "video/mp4": true
            };
            break;
	case "image":
            acceptedTypesArr = {
              "image/jpeg": true,
              "image/png": true,
			  "image/gif": true
            };
            break;
    }
    var holder = document.getElementById(holder),
        holderContents = holder.getElementsByClassName("cell")[0],
        tests = {
          filereader: typeof FileReader != "undefined",
          dnd: "draggable" in document.createElement("span"),
          formdata: window.FormData,
          progress: "upload" in new XMLHttpRequest
        }, 
        support = {
          filereader: document.getElementById("filereader"),
          formdata: document.getElementById("formdata"),
          progress: document.getElementById("progress")
        },
        acceptedTypes = acceptedTypesArr,
        progress = document.getElementById("uploadprogress"),
        fileupload = document.getElementById("upload");
        
   var fup = {
        fileSelectBtn : $('<a class="browse" href="">Browse Files</a>'),
        fileSelector: $('<input type="file" multiple />'),
        outputArea: undefined,
        listImages: function(evt){
            var files = evt.target.files;				
            readfiles(files);
        },
        init: function(cssSelector){
            fup.outputArea = $(cssSelector);
            fup.outputArea.html(fup.fileSelectBtn);
            fup.fileSelector.on("change", fup.listImages);
            
            fup.fileSelectBtn.on("click",function(){
                fup.fileSelector.click();
                return false;
            });
        }
   }        
   window.fileup = fup;
   fup.init(".fileupload");
    
    function readfiles(files) {

        var formData = tests.formdata ? new FormData() : null;
          if (tests.formdata) formData.append(type, files[0]);
    
        if (tests.formdata) {
           if (acceptedTypes[files[0].type] === true) {
			   if(files[0].size < 6000000) {
				   $.ajax({
					url: "/scripts/profile/upload-photo.php"+(groupID != 'undefined' ? "?group="+groupID : ""),
					type: 'POST',
					data: formData,
					cache: false,
					processData: false,
					contentType: false, 
					xhrFields: {
						onprogress: function (e) {
							if (e.lengthComputable) {
								console.log(e.loaded / e.total * 100 + '%');
								$("#uploadprogress").val(e.loaded / e.total * 100);
							}
						}
					},
					success: function(data, textStatus, jqXHR) {
						data = $.trim(data);
						if (data.substring(0, 8) == "SUCCESS:") {
							html = data.substring(8);
							$("#no-photos").hide();
							$("#photos-container").append(html);
							$("#lightscreen").slideUp("fast");
							attachPhotoPageActions();
						}
						else holderContents.innerHTML = '<div style="color:red;">' + data + '</div>';
					}
				  });
			   }
			   else {
				   holderContents.innerHTML = '<div style="color:red;">File is too big. File must be under 6MB.</div>';
			   }
          } else {
            holderContents.innerHTML = '<div style="color:red;">File Type Not Allowed</div>';
          }
         if(files[0].size < 6000000) holderContents.innerHTML = '<img src="/img/loading.gif" />';
        }
    }
    
    if (tests.dnd) { 
      var prevClass = holder.className;
      holder.ondragover = function () { this.className = "table hover"; return false; };
      holder.ondragend = function () { this.className = prevClass; return false; };
      holder.ondrop = function (e) {
        this.className = prevClass;
        e.preventDefault();
        readfiles(e.dataTransfer.files);
      }
    } else {
      fileupload.className = "hidden";
      fileupload.querySelector("input").onchange = function () {
        readfiles(this.files);
      };
    }
} 