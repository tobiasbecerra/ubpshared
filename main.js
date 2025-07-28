// NavBar
function hideIconBar(){
    var iconBar = document.getElementById("iconBar");
    var navigation = document.getElementById("navigation");
    iconBar.setAttribute("style", "display:none;");
    navigation.classList.remove("hide");
}

function showIconBar(){
    var iconBar = document.getElementById("iconBar");
    var navigation = document.getElementById("navigation");
    iconBar.setAttribute("style", "display:block;");
    navigation.classList.add("hide");
}

// Comment
function showComment(){
    var commentArea = document.getElementById("comment-area");
    commentArea.classList.remove("hide");
}

// Reply
function showReply(){
    var replyArea = document.getElementById("reply-area");
    replyArea.classList.remove("hide");
}

// Redirección automática luego del registro exitoso (5 segundos)
document.addEventListener("DOMContentLoaded", () => {
    const mensaje = document.querySelector(".mensaje-exito");
    if (mensaje) {
        setTimeout(() => {
            window.location.href = "login.php";
        }, 5000); // redirige en 5 segundos
    }
});
