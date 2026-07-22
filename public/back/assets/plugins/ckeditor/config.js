/**
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function (config) {
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';
    config.extraPlugins = 'html5video, youtube, aparat';


    config.language = 'fa';

    // Upload file
    config.filebrowserBrowseUrl = '/file-manager/ckeditor';

    // Upload image
    config.filebrowserImageBrowseUrl = '/file-manager/ckeditor';

    config.height = 500;
};
