function swal_alert_response(data) {
    if (data.status) {
        Swal.fire({
            position: 'top-end',
            title: data.title,
            text: data.msg,
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'bg-light'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then();
    } else {
        Swal.fire({
            position: 'top-end',
            title: data.title,
            text: data.msg,
            icon: 'error',
            timer: 3000,
            showConfirmButton: false,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'swal-error-container'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then();
    }
}

//Message Handle
function success_message(msg) {
    let x = document.getElementById("snackbar-success");
    x.innerHTML = msg;
    x.className = "show";
    setTimeout(function () {
        x.className = x.className.replace("show", "");
    }, 3000);
}

function warning_message(msg) {
    let x = document.getElementById("snackbar-warning");
    x.innerHTML = msg;
    x.className = "show";
    setTimeout(function () {
        x.className = x.className.replace("show", "");
    }, 3000);
}