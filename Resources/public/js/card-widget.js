var CardWidget = function(params) {
    this.stripeToken = '';
    this.cardId = '';
    this.stripeCcLastFour = '';
    this.stripeCcType = '';
    this.stripeCcFingerprint = '';
    this.lastCcNbr = '';
    this.postUrl = params.postUrl;
    this.buttonEl = params.buttonEl;
    this.stripeContainer = params.stripeContainer;
    this.code = params.code;
    this.stripePostData = {
        payment_method : params.code
    };
    this.attachEvents();
    return this;
};

CardWidget.prototype = {
    attachEvents : function() {
        var widget = this;

        widget.buttonEl.on('click', function(e){
            var buttonEl = $(this);
            buttonEl.hide();
            buttonEl.siblings('.spinner').show();
            widget.handler(buttonEl);
        });

        return widget;
    },
    post : function() {
        var widget = this;

        $.ajax({
            url: widget.postUrl,
            dataType: 'json',
            type: 'POST',
            data: widget.stripePostData
        }).error(function(jqxhr, status, errorThrown){

            widget.buttonEl.show();
            widget.buttonEl.siblings('.spinner').hide();

        }).done(function(response){

            widget.buttonEl.show();
            widget.buttonEl.siblings('.spinner').hide();

            // handle response
            if (typeof(response['success']) != 'undefined' && response.success == 1) {
                widget.postSuccess(response);
            } else {
                widget.postError(response);
            }
        });
    },
    postSuccess : function(response) {
        console.log(response);
        // show success message, redirect, etc
    },
    postError : function(response) {
        var widget = this;
        var ccNbrEl = widget.stripeContainer.find("input[data-stripe='number']");
        if (!ccNbrEl.parent().hasClass('has-error')) {
            ccNbrEl.parent().addClass('has-error');
        }
        if (!ccNbrEl.parent().hasClass('form-group')) {
            ccNbrEl.parent().addClass('form-group');
        }
    },
    handler : function(buttonEl) {
        var widget = this;

        try {

            var ccNbrEl = widget.stripeContainer.find("input[data-stripe='number']");
            var cvcEl = widget.stripeContainer.find("input[data-stripe='cvc']");
            var expMonthEl = widget.stripeContainer.find("select[data-stripe='exp-month']");
            var expYearEl = widget.stripeContainer.find("select[data-stripe='exp-year']");
            var replaceCardEl = widget.stripeContainer.find("input[name='replace_card']");

            ccNbrEl.parent().removeClass('has-error');
            cvcEl.parent().removeClass('has-error');
            expMonthEl.parent().removeClass('has-error');
            expYearEl.parent().removeClass('has-error');

            var ccNbr = ccNbrEl.val();
            var cvc = cvcEl.val();
            var expMonth = expMonthEl.val();
            var expYear = expYearEl.val();

            if (ccNbr.length > 10) {
                widget.stripeCcLastFour = ccNbr.substring((ccNbr.length - 4));
                widget.stripeCcType = $.payment.cardType(ccNbr);
            }

            var stripeRequest = {
                number: ccNbr,
                cvc: cvc,
                exp_month: expMonth,
                exp_year: expYear
            };

            var isValid = true;
            if (ccNbr.length < 12 || !$.payment.validateCardNumber(ccNbr)) {
                isValid = false;
                if (!ccNbrEl.parent().hasClass('has-error')) {
                    ccNbrEl.parent().addClass('has-error');
                }
                if (!ccNbrEl.parent().hasClass('form-group')) {
                    ccNbrEl.parent().addClass('form-group');
                }
            }

            if (!$.payment.validateCardExpiry(expMonth, expYear)) {
                isValid = false;
                if (!expMonthEl.parent().hasClass('has-error')) {
                    expMonthEl.parent().addClass('has-error');
                }
                if (!expYearEl.parent().hasClass('has-error')) {
                    expYearEl.parent().addClass('has-error');
                }
                if (!expMonthEl.parent().hasClass('form-group')) {
                    expMonthEl.parent().addClass('form-group');
                }
            }

            if (!$.payment.validateCardCVC(cvc)) {
                isValid = false;
                if (!cvcEl.parent().hasClass('has-error')) {
                    cvcEl.parent().addClass('has-error');
                }
                if (!cvcEl.parent().hasClass('form-group')) {
                    cvcEl.parent().addClass('form-group');
                }
            }

            if (isValid) {
                // use the token we already generated
                if (widget.stripeToken.length > 3 && ccNbr == widget.lastCcNbr) {
                    if (replaceCardEl.is(':checked')) {
                        widget.stripePostData.replace_card = 1;
                    } else {
                        widget.stripePostData.replace_card = 0;
                    }
                    widget.post();
                } else {

                    Stripe.card.createToken(stripeRequest, function(status, response) {
                        if (typeof response.id != 'undefined') {
                            widget.stripeToken = response.id;
                            widget.stripePostData.token = widget.stripeToken;
                            widget.stripePostData.cc_type = widget.stripeCcType;
                            widget.stripePostData.cc_last_four = widget.stripeCcLastFour;
                            widget.stripePostData.cc_fingerprint = widget.stripeCcFingerprint;
                            widget.stripePostData.exp_month = widget.expMonth;
                            widget.stripePostData.exp_year = widget.expYear;
                            if (typeof response.card.id != 'undefined') {
                                widget.cardId = response.card.id;
                                widget.stripePostData.card_id = response.card.id;
                            }
                            if (replaceCardEl.is(':checked')) {
                                widget.stripePostData.replace_card = 1;
                            } else {
                                widget.stripePostData.replace_card = 0;
                            }
                            widget.lastCcNbr = ccNbr;
                            widget.post();
                        }
                    });
                }
            }
        } catch(e) {

            widget.buttonEl.show();
            widget.buttonEl.siblings('.spinner').hide();
        }

        return true;
    }
};
