var DialogConfig = {
    'login' : {
        'element' : '#login-dialog',
        'closeElement'  : '.close-login-dialog',
        'openElement'   : '.open-login-dialog',
        'active'        : false
    },
    'registration' : {
        'element' : '#registration-dialog',
        'closeElement'  : '.close-registration-dialog',
        'openElement'   : '.open-registration-dialog',
        'active'        : false
    },
    'request-password-reset' : {
        'element' : '#request-password-reset-dialog',
        'closeElement'  : '.close-request-password-reset-dialog',
        'openElement'   : '.open-request-password-reset-dialog',
        'active'        : false
    },
    'reset-password' : {
        'element' : '#reset-password-dialog',
        'closeElement'  : '.close-reset-password-reset-dialog',
        'openElement'   : '.open-reset-password-dialog',
        'active'        : false,
        'cookieName'   : 'rpt'
    },
    'verify-phone' : {
        'element' : '#verify-phone-dialog',
        'closeElement'  : '.close-verify-phone-dialog',
        //'openElement'   : '.open-verify-phone-dialog',
        'active'        : false
    }
};

/**
 *
 */
var Dialog = (function() {

    Dialog.prototype.name = '';
    Dialog.prototype.element = null;
    Dialog.prototype.closeElement = null;
    Dialog.prototype.openElement = null;
    Dialog.prototype.active = false;
    Dialog.prototype.cookieName = false;

    function Dialog(name) {
        this.name = name;
        this.setAttributesFromConfig();
        this.initEvents();
    }

    Dialog.prototype.setAttributesFromConfig = function() {
        var attributes = DialogConfig[this.name];
        for (var i in attributes) {
            this.set(i, attributes[i]);
        }
    };

    Dialog.prototype.set = function(attribute, value) {
        if(this[attribute] !== undefined) {
            this[attribute] = value;
        }
    };

    Dialog.prototype.get = function(attribute) {
        return this[attribute];
    };

    Dialog.prototype.beforeOpen = function() {
        return true;
    };

    Dialog.prototype.open = function() {
        if (this.beforeOpen()) {
            $(this).trigger('beforeOpen', this);
            $(this.get('element')).fadeIn((function(){
                this.set('active', true);
                $(this).trigger('afterOpen', this);
            }).bind(this));
        }
    };

    Dialog.prototype.close = function(attribute) {
        if (this.get('active')) {
            $(this).trigger('beforeClose');
            $(this.get('element')).fadeOut((function() {
                var form = $(this.get('element')).find('form');
                if (form && form[0]) {
                    form[0].reset();
                }
                this.set('active', false);
                $(this).trigger('afterClose');
            }).bind(this));
        }
    };

    Dialog.prototype.initEvents = function() {

        $(this.get('openElement')).click((function(e) {
            window.location.hash = '';
            this.open();
        }).bind(this));

        $(this.get('closeElement')).click((function(e){
            window.location.hash = '';
            e.preventDefault();
            e.stopPropagation();
            this.close();
        }).bind(this));

        $(document).on('keyup', (function(e) {
            if (e.keyCode == 27 && this.get('active')) {
                this.close();
            }
        }).bind(this));
    };

    return Dialog;
})();
