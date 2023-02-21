
document.addEventListener("DOMContentLoaded", function () {
    (function ($) {
        'use strict';

        if (typeof uploadDropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }


        function wp_membership_fetch(data, is_formular = true, callback) {
            let formData = new FormData();
            if (is_formular) {
                let input = new FormData(data);
                for (let [name, value] of input) {
                    formData.append(name, value);
                }
            } else {
                for (let [name, value] of Object.entries(data)) {
                    formData.append(name, value);
                }
            }
            formData.append('_ajax_nonce', wml_ajax_obj.nonce);
            formData.append('action', 'MembershipLogin');

            fetch(wml_ajax_obj.ajax_url, {
                method: 'POST',
                body: formData
            }).then((response) => response.json())
                .then((result) => {
                    if (typeof callback === 'function') {
                        document.addEventListener("load", callback(result));
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }


        $(document).on('click', '.mbl-action', function () {
            let type = $(this).attr('data-type');
            let btnGroup = $('.mbl-action');
            let colSettings = '';
            let colAdd = '';
            let target;
            let parent;
            let id;
            let formData;
            let handle;
            let swal;

            $(this).attr('data-target') ? target = $(this).attr('data-target') : target = '';
            $(this).attr('data-parent') ? parent = $(this).attr('data-parent') : parent = '';
            $(this).attr('data-handle') ? handle = $(this).attr('data-handle') : handle = '';
            $(this).attr('data-id') ? id = $(this).attr('data-id') : id = '';
            switch (type) {
                case 'membership_settings':
                    btnGroup.prop('disabled', false);
                    $(this).prop('disabled', true);
                    new bootstrap.Collapse(target, {
                        toggle: true,
                        parent: parent
                    })
                    break;
                case'membership_login_table':
                    btnGroup.prop('disabled', false);
                    $(this).prop('disabled', true);
                    new bootstrap.Collapse(target, {
                        toggle: true,
                        parent: parent
                    })
                    break;

                case 'wp_membership_template_handle':
                    formData = {
                        'method': type,
                        'handle': handle,
                        'id': id,
                        'target': target,
                        'parent': parent
                    }
                    break;
                case'back-member-table':
                    dataTableMembershipsOverview.draw('page');
                    new bootstrap.Collapse(target, {
                        toggle: true,
                        parent: parent
                    })
                    break;
                case 'delete_wp_membership':
                    formData = {
                        'method': type,
                        'id': id
                    }
                    swal = {
                        title: wml_ajax_obj.js_lang.delete_title,
                        body: wml_ajax_obj.js_lang.delete_subtitle,
                        btn: wml_ajax_obj.js_lang.delete_btn_txt
                    }
                    swal_fire_app_delete(formData, swal);
                    return false;
                case'document_table':
                    btnGroup.prop('disabled', false);
                    $(this).prop('disabled', true);
                    if (Dropzone.instances.length > 0) Dropzone.instances.forEach(bz => bz.destroy());
                    dataTableDocuments.draw('page');
                    new bootstrap.Collapse(target, {
                        toggle: true,
                        parent: parent
                    })
                    break;
                case'document_upload_template':
                    btnGroup.prop('disabled', false);
                    $(this).prop('disabled', true);
                      formData = {
                          method: type,
                          target:target,
                          parent: parent
                      }
                    break;
                case 'edit_document_template':
                    formData = {
                        method: type,
                        parent:parent,
                        target: target,
                        id: id
                    }
                    break;
                case'backToDocTable':
                    dataTableDocuments.draw('page');
                    new bootstrap.Collapse('#colMembershipDocumentsTable', {
                        toggle: true,
                        parent: '#collParent'
                    })
                    break;
                case'delete_document':
                    formData = {
                        'method': type,
                        'id': id,
                        'handle': handle
                    }
                    swal = {
                        title: wml_ajax_obj.js_lang.delete_file_title,
                        body: wml_ajax_obj.js_lang.delete_subtitle,
                        btn: wml_ajax_obj.js_lang.delete_file_btn
                    }
                    swal_fire_app_delete(formData, swal);
                    return false;
                case 'delete_group':
                    formData = {
                        'method': type,
                        'id': id,
                    }
                    swal = {
                        title: wml_ajax_obj.js_lang.delete_group_title,
                        body: `<small class="small-xl">${wml_ajax_obj.js_lang.delete_group_subtitle}</small><br>${wml_ajax_obj.js_lang.delete_subtitle}<br>`,
                        btn: wml_ajax_obj.js_lang.delete_group_btn
                    }
                    swal_fire_app_delete(formData, swal);
                    return false;
                case'change-readonly':
                    if($(this).prop('checked')){
                        $(target).prop('readonly', false)
                    } else {
                        $(target).prop('readonly', true)
                    }
                    break;
            }

            if (formData) {
                wp_membership_fetch(formData, false, mbl_action_callback)
            }
        })

        function mbl_action_callback(data) {
            if (data.status) {
                switch (data.type) {
                    case 'wp_membership_template_handle':
                        $(data.target).html(data.template)
                        new bootstrap.Collapse(data.target, {
                            toggle: true,
                            parent: data.parent
                        })
                        break;
                    case'delete_wp_membership':
                        dataTableMembershipsOverview.draw('page');
                        swal_alert_response(data);
                        break;
                    case'document_upload_template':
                        $(data.target).html(data.template);
                        init_membership_upload_dropzone();
                        new bootstrap.Collapse(data.target, {
                            toggle: true,
                            parent: data.parent
                        })
                        break;
                    case 'edit_document_template':
                        $(data.target).html(data.template);
                        new bootstrap.Collapse(data.target, {
                            toggle: true,
                            parent: data.parent
                        })
                        break;
                    case 'delete_document':
                        if(data.handle === 'overview_table'){
                            dataTableDocuments.draw('page')
                        }
                        if(data.handle === 'upload_table') {
                            $('#current'+data.id).parent('tr').remove();
                        }
                        swal_alert_response(data);
                        break;
                    case 'delete_group':
                        $('#group'+data.id).remove();
                        swal_alert_response(data);
                        break;
                }
            } else {
                if (data.title) {
                    swal_alert_response(data);
                } else {
                    warning_message(data.msg);
                }
            }
        }

        $(document).on('submit', '.membership-admin-formular', function (event) {
            let form = $(this).closest("form").get(0);
            wp_membership_fetch(form, true, membership_formular_callback);
            event.preventDefault();
        });

        function membership_formular_callback(data) {
            switch (data.type) {
                case 'membership_handle':
                    dataTableMembershipsOverview.draw('page');
                    new bootstrap.Collapse('#colMembershipOverview', {
                        toggle: true,
                        parent: '#collParent'
                    })
                    break;
                case 'add_document_group':
                    if(data.status){
                        const groupModal =  bootstrap.Modal.getOrCreateInstance('#addGroupModal', {
                            keyboard: false,
                        })
                        groupModal.hide();
                        $('.membership-admin-formular').trigger('reset');
                        let group = document.getElementById('docGroups');
                        group.insertAdjacentHTML('beforeend', data.template);
                    } else {
                        warning_message(data.msg);
                    }
                   break;
            }
            if(data.title) {
                swal_alert_response(data)
            }

        }

        let membershipSendFormTimeout;
        $(document).on('input propertychange change', '.membership-admin-autosave', function () {
            let formData = $(this).closest("form").get(0);
            let target = $(this).attr('data-target');
            let spin = $(target);
            spin.html('');
            spin.addClass('wait');
            clearTimeout(membershipSendFormTimeout);
            membershipSendFormTimeout = setTimeout(function () {
                wp_membership_fetch(formData, true, membership_formular_autosave_callback);
            }, 1000);
        });

        function membership_formular_autosave_callback(data) {
            switch (data.type) {
                case 'update_plugin_settings':
                    show_ajax_spinner(data, '.plugin-settings');
                    break;
                case 'membership_handle':
                    show_ajax_spinner(data, '.membership');
                    break;
            }
        }

        let dataTablesMembership = $('#dataTablesMembership');
        if (dataTablesMembership) {
            wpMembershipTable();
        }

        let dataTablesDocumentsDownload = $('#dataTablesDocumentsDownload');
        if(dataTablesDocumentsDownload){
            wpMembershipDownloadTable();
        }

        let dataTablesDocuments = $('#dataTablesDocuments');
        if(dataTablesDocuments){
            documentsTable();
        }

        function swal_fire_app_delete(data, swal) {
            Swal.fire({
                title: swal.title,
                reverseButtons: true,
                html: `<span class="swal-delete-body">${swal.body}</span>`,
                confirmButtonText: swal.btn,
                cancelButtonText: wml_ajax_obj.js_lang.Cancel,
                showClass: {
                    //popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-delete-container'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    wp_membership_fetch(data, false, mbl_action_callback)
                }
            });
        }

        function show_ajax_spinner(data, target = '') {
            let msg = '';
            if (data.status) {
                msg = '<i class="text-success fw-bold bi bi-check2-circle"></i>&nbsp; Saved! Last: ' + data.msg;
            } else {
                msg = '<i class="text-danger bi bi-exclamation-triangle"></i>&nbsp; ' + data.msg;
            }
            let spinner = document.querySelector(target + '.ajax-status-spinner');
            spinner.classList.remove('wait');
            spinner.innerHTML = msg;
        }

    })(jQuery);
});
