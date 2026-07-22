/*
 * Aparat Embed Plugin
 *
 * @author Jonnas Fonini <jonnasfonini@gmail.com>
 * @version 2.1.14
 */
(function() {
    CKEDITOR.plugins.add('aparat', {
        lang: ['en', 'fa'],
        init: function(editor) {
            editor.addCommand('aparat', new CKEDITOR.dialogCommand('aparat', {
                allowedContent: 'div{*}(*); iframe{*}[!width,!height,!src,!frameborder,!allowfullscreen,!allow]; object param[*]; a[*]; img[*]'
            }));

            editor.ui.addButton('Aparat', {
                label: editor.lang.aparat.button,
                toolbar: 'insert',
                command: 'aparat',
                icon: this.path + 'images/icon.png'
            });

            CKEDITOR.dialog.add('aparat', function(instance) {
                var video,
                    disabled = editor.config.aparat_disabled_fields || [];

                return {
                    title: editor.lang.aparat.title,
                    minWidth: 510,
                    minHeight: 200,
                    onShow: function() {
                        for (var i = 0; i < disabled.length; i++) {
                            this.getContentElement('aparatPlugin', disabled[i]).disable();
                        }
                    },
                    contents: [{
                        id: 'aparatPlugin',
                        expand: true,
                        elements: [


                            {
                                type: 'hbox',
                                widths: ['100%'],
                                children: [{
                                    id: 'apTxtUrl',
                                    type: 'text',
                                    label: editor.lang.aparat.apTxtUrl,

                                    validate: function() {
                                        if (this.isEnabled()) {
                                            if (!this.getValue()) {
                                                alert(editor.lang.aparat.noCode);
                                                return false;
                                            } else {
                                                video = apVidId(this.getValue());

                                                if (this.getValue().length === 0 || video === false) {
                                                    alert(editor.lang.aparat.invalidUrl);
                                                    return false;
                                                }
                                            }
                                        }
                                    }
                                }]
                            },
                        ]
                    }],
                    onOk: function() {
                        var content = '';
                        var responsiveStyle = '';

                        if (true) {

                            content += '<div class="h_iframe-aparat_embed_frame"><span style="display: block;padding-top: 57%">.</span><iframe src="https://www.aparat.com/video/video/embed/videohash/' + video + '/vt/frame" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe></div>'

                        }

                        var element = CKEDITOR.dom.element.createFromHtml(content);
                        var instance = this.getParentEditor();
                        instance.insertElement(element);
                    }
                };
            });
        }
    });
})();


/**
 * JavaScript function to match (and return) the video Id
 * of any valid Aparat Url, given as input string.
 * @author: Stephan Schmitz <eyecatchup@gmail.com>
 * @url: http://stackoverflow.com/a/10315969/624466
 */
function apVidId(url) {
    var p = /^(?:https?:\/\/)?(?:www\.)?(?:aparat\.com\/v\/)(\w*)(?:\S+)?$/;
    return (url.match(p)) ? RegExp.$1 : false;
}