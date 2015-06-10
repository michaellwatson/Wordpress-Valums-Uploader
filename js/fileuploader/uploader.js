$(document).ready(function() {
var uploader = new qq.FileUploader({
            // pass the dom node (ex. $(selector)[0] for jQuery users)
            element: document.getElementById('file-uploader'),
            // path to server-side upload script
            action: edd_scripts.ajaxurl,
			
			params: {action:'upload_image'},
            onComplete: function (id, fileName, responseJSON) {
                //alert(responseJSON['filename']);
                
				$('.qq-upload-list li').remove();
                
				$('.ipb').width(0);
				
				var str = responseJSON['filename'];
				//alert(str);
				
            },
			onSubmit: function(id, fileName){
				//$('.qq-upload-list').hide();	
			},
			onProgress: function(id, fileName, loaded, total){
				//console.log(loaded);
				//console.log(total);
				var width = $('.imagesProgress').width()*(((loaded/total)*100)/100);
				console.log(width);
				$('.ipb').width(width);
				/*var thenum = $('.qq-upload-size').html();
				var numOnly = thenum.replace( /^\D+/g, '');
				console.log(numOnly);*/
			},
			onCancel: function(id, fileName){},
			onError: function(id, fileName, xhr){},
			multiple: false
        });
		//$('.qq-upload-button').prepend('ADD PHOTO');
});
