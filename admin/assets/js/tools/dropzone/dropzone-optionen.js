document.addEventListener("DOMContentLoaded", function (event) {
});

let ClearBtnBox = document.getElementById("clearBtnBox");
let CancelBtnBox = document.getElementById("CancelBtnBox");
let uploadTable = document.getElementById("uploadTable");
let uploadDropzone = '';

function init_membership_upload_dropzone() {
    //let s = cmsSettings.settings.upload;
    let resetBtn = document.getElementById('clear-dropzone');
    let outContainer = document.getElementById('outputData');
    let sigColl = document.getElementById('logoUploadCollapse');

    let dropzoneContainer = document.getElementById('fileUploadDropzone');
    let accept;
    if (dropzoneContainer.hasAttribute('data-accept')) {
        accept = dropzoneContainer.getAttribute('data-accept');
    } else {
        accept = '';
    }
    let multiple;
    //s.count > 1 ? multiple = true : multiple = false;
    uploadDropzone = new Dropzone(dropzoneContainer, {
        url: wml_ajax_obj.ajax_url,
        paramName: "file",
        uploadMultiple: false,
        //maxFilesize: s.upload_size,
        //maxFiles: s.count,
        parallelUploads: 2,
        //resizeWidth: s.max_width,
        //resizeHeight: s.max_height,
        dictDefaultMessage: 'Dateien hier per Drag & Drop ablegen oder klicken.',
        dictInvalidFileType: 'Sie können keine Dateien dieses Typs hochladen.',
        dictFallbackMessage: 'Ihr Browser unterstützt keine Drag\'n\'Drop-Datei-Uploads.',
        dictFileTooBig: 'Datei ist zu groß ({{Dateigröße}}MiB). Maximale Dateigröße: {{maxFilesize}}MiB.',
        addRemoveLinks: false,
        autoProcessQueue: true,
        dictRemoveFile: 'Datei entfernen',
        init: function () {
            let _this = this;
            let appDropzone = this;
            this.on("addedfile", function (file) {
                resetBtn.removeAttribute('disabled');
                if (CancelBtnBox) {
                    CancelBtnBox.classList.remove("cancelHide");
                }
            });

            // Update the total progress bar
            this.on("totaluploadprogress", function (totalBytes, totalBytesSent, progress) {

            });

            this.on("sending", function (file, xhr, formData) {
                formData.append("filesize", file.size);
                formData.append("lastModified", file.lastModified);
                formData.append("mimeType", file.type);
                let inputData = document.querySelector('.upload-data-image');
                let input = new FormData(inputData);
                for (let [name, value] of input) {
                    formData.append(name, value);
                }
            });

            // Hide the total progress bar when nothing's uploading anymore
            this.on("queuecompvare", function (progress) {
                if (CancelBtnBox) {
                    CancelBtnBox.classList.add("cancelHide");
                }

            });

            this.on("complete", function (file) {
                //file.previewElement.querySelector('.dz-progress').remove()

                if (CancelBtnBox) {
                    CancelBtnBox.classList.add("cancelHide");
                }
            })

            this.on("success", function (file, response) {
                if (response.status) {

                    switch (response.handle) {
                        case'dokument_upload':
                            let html = document.createElement('tr');
                            html.innerHTML = `<td id="current${response.id}" class="align-middle text-center"><span class="table-file file ext_${response.ext}"></span></td>
                               <td class="align-middle text-start text-truncate">${response.filename}</td>
                               <td class="align-middle text-start"><span class="lh-1 mb-0">${response.date}<small class="small-lg d-block">${response.time} ${wml_ajax_obj.js_lang.clock}</small> </span></td>
                               <td class="align-middle text-center">${response.size}</td>
                               <td class="align-middle text-start">${response.type}</td> 
                               <td class="align-middle text-center">
                               <button data-id="${response.id}" data-type="delete_document" data-handle="upload_table" class="mbl-action btn btn-outline-danger text-nowrap btn-sm"><i class="bi bi-trash"></i>&nbsp; ${wml_ajax_obj.js_lang.delete}</button>
                               </td>`;
                            document.getElementById('uploadTable').classList.remove('d-none')
                            document.getElementById('uploadedFiles').appendChild(html);
                       break;
                    }
                    resetBtn.setAttribute('disabled', 'disabled');
                    setTimeout(() => {
                        this.removeFile(file);
                    }, 2500);
                }
            });

            this.on("error", function (file, response) {
                file.previewElement.querySelector('.dz-progress').remove()
                resetBtn.removeAttribute('disabled');
                if (CancelBtnBox) {
                    CancelBtnBox.classList.remove("opacityHide");
                }

                setTimeout(() => {
                   // this.removeFile(file);
                }, 3000);
            });

            this.on("compvare", function (file) {
                console.log(file)
            });

            document.querySelector("button#clear-dropzone").addEventListener("click", function () {
                _this.removeAllFiles();
                this.blur();
                resetBtn.setAttribute("disabled", 'disabled');
                if (CancelBtnBox) {
                    CancelBtnBox.classList.add("cancelHide");
                }
            });

            /* document.querySelector("button#cancel-download").addEventListener("click", function () {
                 _this.removeAllFiles(true);
                 this.blur();
                 if(CancelBtnBox){
                     CancelBtnBox.classList.add("cancelHide");
                 }
             });*/
        }
    });
}
