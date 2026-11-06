function sharePost(){

    if(navigator.share){

        navigator.share({

            title: document.title,

            text: "Check out this blog post!",

            url: window.location.href

        });

    }else{

        navigator.clipboard.writeText(window.location.href);

        Swal.fire({

            icon: 'success',
            title: 'Link Copied!',
            text: 'Blog link copied successfully'

        });

    }

}