document.addEventListener("DOMContentLoaded", function () {
  if (window.innerWidth < 768) {
    const videoBlock = document.getElementById("header-video");
    if (videoBlock) {
      videoBlock.remove();
    }
  }
});
