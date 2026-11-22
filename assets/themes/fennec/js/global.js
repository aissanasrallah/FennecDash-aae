$.fn.appFormValidator.internal_options.on_required_add_symbol = false;

$(function() {

    // Fix for dropdown in tables
    $('body').on('shown.bs.dropdown', '.btn-group', function() {
        $(this).closest('.table-responsive').css("overflow", "inherit");
    });

    $('body').on('hidden.bs.dropdown', '.btn-group', function() {
        $(this).closest('.table-responsive').css("overflow", "auto");
    });

    $("body").on("rendered.bs.select", "select", function () {
        $(this).parents().removeClass("select-placeholder");
        $(this)
          .parents(".form-group")
          .find(".select-placeholder")
          .removeClass("select-placeholder");
      });

    appSelectPicker($('body').find('select'));
    appProgressBar();
    appLightbox();
    appColorPicker();
    appDatepicker();

    // Lightbox for knowledge base images
    $.each($('.kb-article').find('img'), function() {
        if(!$(this).parent().is('a')){
            $(this).wrap('<a href="' + $(this).attr('src') + '" data-lightbox="kb-attachment"></a>');
        }
    });

    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

    // Init popovers
    $('body').popover({
        selector: '[data-toggle="popover"]'
    });

    $('.article_useful_buttons button').on('click', function(e) {
        e.preventDefault();
        var data = {};
        data.answer = $(this).data('answer');
        data.articleid = $('input[name="articleid"]').val();
        $.post(site_url + 'knowledge_base/add_kb_answer', data).done(function(response) {
            response = JSON.parse(response);
            if (response.success == true) {
                $(this).focusout();
            }
            $('.answer_response').html(response.message);
        });
    });

    $('#identityConfirmationForm').appFormValidator({
        rules: {
            acceptance_firstname: 'required',
            acceptance_lastname: 'required',
            signature: 'required',
            acceptance_email: {
                email: true,
                required: true
            }
        },
        messages: {
            signature: {
                required: app.lang.sign_document_validation,
            },
        },
    });

    $('body.identity-confirmation #accept_action').on('click', function() {
        var $submitForm = $('#identityConfirmationForm');
        if ($submitForm.length && !$submitForm.validate().checkForm()) {
            $('#identityConfirmationModal').modal({ show: true, backdrop: 'static', keyboard: false });
        } else {
            $(this).prop('disabled', true);
            $submitForm.submit();
        }
        return false;
    });

    // Dark mode toggle & body class sync (also applies on headerless preview pages)
    var currentTheme = document.documentElement.getAttribute('data-bs-theme') || localStorage.getItem('fennec-client-theme') || localStorage.getItem('fennec-theme') || 'light';
    if (currentTheme === 'dark') {
      document.documentElement.setAttribute('data-bs-theme', 'dark');
      document.body.classList.add('dark-mode');
    }
    var themeToggle = $('.theme-toggle');
    if (themeToggle.length) {
      themeToggle.find('i').attr('class', currentTheme === 'dark' ? 'fa-regular fa-sun' : 'fa-regular fa-moon');
      themeToggle.on('click', function() {
        var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('fennec-client-theme', next);
        themeToggle.find('i').attr('class', next === 'dark' ? 'fa-regular fa-sun' : 'fa-regular fa-moon');
        document.body.classList.toggle('dark-mode', next === 'dark');
        console.log('Dark mode: toggled to', next);
      });
    }
});

function createDropzone(elementId, options) {
    var defaults = appCreateDropzoneOptions({
        paramName: "file",
        uploadMultiple: true,
        parallelUploads: 20,
        maxFiles: 20,
        accept: function(file, done) {
            done();
        },
        success: function(file, response) {
            if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                window.location.reload();
            }
        },
    });

    var settings = $.extend({}, defaults, options);
    new Dropzone(elementId, settings);
}

function change_contact_language(element) {
    var path = 'authentication/change_language/' + $(element).val();
    window.location.href = site_url + path;
}
