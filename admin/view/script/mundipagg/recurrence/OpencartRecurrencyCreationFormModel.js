var OpencartRecurrencyCreationFormModel = function(formController) {
    this.formController = formController;
};

OpencartRecurrencyCreationFormModel.prototype.init = function() {
    var _self = this;
    //replace form action
    var formAction = $('#form-product').attr('action');
    var recurrenceType = $('#recurrence-type').val();
    var splitData = formAction.split("?");
    var baseUrl = splitData[0];

    splitData = splitData[1].split("&");

    splitData[0] =
        'route=extension/payment/mundipagg/' + recurrenceType +
        '&is_' + recurrenceType
    ;

    var finalUrl =
        baseUrl + '?' +
        splitData.join("&") +
        "&action=save&mundipagg_" +
        recurrenceType.replace('s', '')
    ;

    $('#form-product').attr('action', finalUrl);

    //defining templateInfoUrl
    splitData[0] = 'route=extension/payment/mundipagg/templates';
    this.templateInfoUrl = baseUrl + '?' + splitData.join("&") + "&action=info";

    //add handler for template add from select
    this.getAddTemplateFromSelectButtonElement().on(
        'click',
        function() {
            this.formController.addTemplateFromSelect();
        }.bind(this)
    );

    //add handler for template snapshop remove button
    $('#template-snapshot-remove').on(
        'click',
        function() {
            this.formController.templateSnapshot = null;
            this.getTemplateSnapshotDetailPanelElement().hide();
            this.getAddTemplateFromSelectButtonElement().show();
            this.getTemplateSelectEditPanelElement().show();
        }.bind(this)
    );

    //add handler for template snapshop edit button
    $('#template-snapshot-edit').on(
        'click',
        function() {
            this.fillTemplateFormWithSnapshotData(this.formController.templateSnapshot);
            this.formController.templateSnapshot = null;
            this.getTemplateSelectElement().val('');
            this.getTemplateSelectElement().change();
            this.getTemplateSnapshotDetailPanelElement().hide();
            this.getAddTemplateFromSelectButtonElement().show();
            this.getTemplateSelectEditPanelElement().show();
        }.bind(this)
    );

    //prepare due type select
    var dueLocation = this.formController.mundipaggRoot.Location.recurrence.template.due.type;
    Object.keys(dueLocation).forEach(function(type){
        $('#expiry_type').append('<option value="'+type+'">'+dueLocation[type].name+'</option>');
    });
    //prepare interval type select
    var intervalLocation = this.formController.mundipaggRoot.Location.recurrence.template.repetition.interval.type;
    Object.keys(intervalLocation).forEach(function(type){
        $('#interval').append('<option value="'+type+'">'+intervalLocation[type].name+'</option>');
    });

    //add handler for mp-add-plan-template-button click
    $('#mp-add-plan-template-button').on('click',function(event){
        var templateSnapshot = {
            dueAt: {
                type: $('#expiry_type').val(),
                value: $('#expiry_date').val()
            },
            repetitions: [
                {
                    cycles: $('#mp-recurrency-cycles').val(),
                    discountType: null,
                    discountValue: null,
                    frequency: $('#frequency').val(),
                    intervalType: $('#interval').val()
                }
            ],
            template: {
                acceptBoleto: $('#checkbox-boleto').prop( "checked" ),
                acceptCreditCard: $('#checkbox-creditcard').prop( "checked" ),
                allowInstallments: $('#allow_installment').val() === '1' ? true : false,
                description: $('#mp-recurrency-description').val(),
                isSingle: false,
                name: $('#mp-recurrency-name').val(),
                trial: $('#mp-recurrency-trial').val(),
                installments: $('#installments').val()
            }
        };
        this.formController.showConfigTable(templateSnapshot);
    }.bind(this));

    //prepare product search autocomplete
    splitData[0] = 'route=extension/payment/mundipagg/plans';
    this.productSearchUrl = baseUrl + '?' + splitData.join("&") + "&action=productSearch";
    var productSearchUrl = this.productSearchUrl;
    var autocompleteOptions = {
        source: productSearchUrl,
        delay: 500,
        minLength: 3,
        response: function( event, ui ) {
            $('#mp-recurrence-product-search-icon')
                .addClass('fa-search')
                .removeClass('fa-cog fa-spin');
        },
        search: function() {
            $(this).removeAttr('data-mp-item-id');
            $('#mp-recurrence-product-search-icon')
                .addClass('fa-cog fa-spin')
                .removeClass('fa-search');
        },
        focus: function( event, ui ) {
            event.preventDefault();
            $('#mp-recurrence-product-search').val(ui.item.label);
        },
        select: function( event, ui ) {
            event.preventDefault();
            _self.addProductToPlan({
                name: ui.item.label,
                id: ui.item.value,
                price: ui.item.price,
                thumb: ui.item.thumb
            });
            $('#mp-recurrence-product-search').val('');
            this.setPlanPrice();
        }.bind(_self)
    };
    $('#mp-recurrence-product-search').autocomplete(autocompleteOptions);

    $("#input-price")
        .attr('readonly', 'readonly')
        .attr('placeholder', 'O preço do plano é definido pela soma dos preços dos produtos incluídos nele');

};

OpencartRecurrencyCreationFormModel.prototype
    .addProductToPlan = function(productData) {
    var html = $('#mp-recurrence-product-row-template').html();
    html = html.replace(/\{product_id\}/g,productData.id);
    html = html.replace(/\{product_name\}/g,productData.name);
    html = html.replace(/\{product_price\}/g, productData.price);
    html = html.replace(/\{product_thumb\}/g,productData.thumb);
    html = html.replace(
        /\{product_cycles\}/g,
        typeof productData.cycles !== 'undefined' ? productData.cycles : '1'
    );
    html = html.replace(
        /\{product_quantity\}/g,
        typeof productData.quantity !== 'undefined' ? productData.quantity : '1'
    );

    var intervalLocation = this.formController.mundipaggRoot.Location.recurrence.template.repetition.interval.type;
    html = html.replace(/\{product_cycle_type\}/g,Object.keys(intervalLocation).shift());

    $('#mp-recurrence-product-table-body').append(html);
};

OpencartRecurrencyCreationFormModel.prototype
    .fillTemplateFormWithSnapshotData = function(templateSnapshot) {
    $('#expiry_type').val(templateSnapshot.dueAt.type);
    $('#expiry_date').val(templateSnapshot.dueAt.value);

    $('#mp-recurrency-cycles').val(templateSnapshot.repetitions[0].cycles);
    $('#frequency').val(templateSnapshot.repetitions[0].frequency);
    $('#interval').val(templateSnapshot.repetitions[0].intervalType);

    $('#checkbox-boleto').prop( "checked", templateSnapshot.template.acceptBoleto);
    $('#checkbox-creditcard').prop( "checked", templateSnapshot.template.acceptCreditCard);
    $('#allow_installment').val(templateSnapshot.template.allowInstallments ? "1" : "0");

    var installments = templateSnapshot.template.installments;

    if (typeof installments === "string") {
        installments = installments.split(",");
    }

    $('#installments').val('');
    installments.forEach(function(installment) {
        installment = installment.replace(/\D/, '');
        $('#installments').tagsinput('add', installment);
    });

    $('#mp-recurrency-description').val(templateSnapshot.template.description);
    $('#mp-recurrency-name').val(templateSnapshot.template.name);
    $('#mp-recurrency-trial').val(templateSnapshot.template.trial);
    $('#checkbox-creditcard').change();
    $('#allow_installment').change();
    $('#expiry_type').change();
};

OpencartRecurrencyCreationFormModel.prototype
    .getTemplateSelectElement = function() {
    return $('#select-subscription');
};

OpencartRecurrencyCreationFormModel.prototype
    .getTemplateSnapshotDetailPanelElement = function() {
    return $('#template-snapshot-detail-panel');
};
OpencartRecurrencyCreationFormModel.prototype
    .getAddTemplateFromSelectButtonElement = function() {
    return $('#add-template-from-select-button');
};
OpencartRecurrencyCreationFormModel.prototype
    .getTemplateSelectEditPanelElement = function() {
    return $('#customized');
};

OpencartRecurrencyCreationFormModel.prototype
    .getTemplateInfoUrl = function() {
    return this.templateInfoUrl;
};

OpencartRecurrencyCreationFormModel.prototype
    .updateTemplateSnapshotDetailPanel = function(templateSnapshotData) {

    var configContainer = $("#mp-recurrence-plan-config-row-container");
    var html = $('#mp-recurrence-plan-config-row-template').html();

    html = html.replace(/\{plan_name\}/g,(function(templateSnapshotData){
        var retn = templateSnapshotData.template.name;
        retn += ' <span class="label label-primary">plan</span>';
        if (templateSnapshotData.template.trial > 0) {
            retn += ' <span class="label label-warning">' +
                templateSnapshotData.template.trial +
                ' day trial</span>';
        }
        return retn;
    })(templateSnapshotData));

    html = html.replace(/\{plan_description\}/g,templateSnapshotData.template.description);

    html = html.replace(/\{plan_payment\}/g,(function(templateSnapshotData){
        var retn = "";
        if (templateSnapshotData.template.acceptBoleto) {
            retn += "<span class='label label-default'>Boleto</span> ";
        }
        if (templateSnapshotData.template.acceptCreditCard) {
            retn += "<span class='label label-default'>Cartão de Crédito</span> ";
        }
        return retn;
    })(templateSnapshotData));

    html = html.replace(/\{plan_due\}/g, (function(templateSnapshotData, mundipaggRoot){
        var retn = mundipaggRoot.Location
            .recurrence.template.due
            .type[templateSnapshotData.dueAt.type].label;
        retn = retn.replace('%d',templateSnapshotData.dueAt.value);

        return "<span class='label label-default'>" + retn + "</span>";

    })(templateSnapshotData, this.formController.mundipaggRoot));

    html = html.replace(/\{plan_cycles\}/g, (function(templateSnapshotData, mundipaggRoot){

        retn = '';

        $.each(templateSnapshotData.repetitions, function(key, value) {
            intervalLabel = mundipaggRoot.Location
                .recurrence.template.repetition
                .interval.type[value.intervalType].label;

            cyclesLabel = mundipaggRoot.Location
                .recurrence.template.repetition.cycle.label;

            label = intervalLabel[
                templateSnapshotData.repetitions[0].frequency > 1 ? 1 : 0
                ];

            retn += '<span class="label label-default">';
            retn += value.cycles;

            if(value.cycles > 1) {
                retn += ' ' + cyclesLabel[1];
            } else {
                retn += ' ' + cyclesLabel[0];
            }

            retn += ' ' + mundipaggRoot.Location.misc.of + ' ';
            retn += value.frequency + ' ' + label + ' ';

            if (value.discountValue != null) {
                discountLabel = mundipaggRoot.Location
                    .recurrence.template.repetition
                    .discount.type[value.discountType].symbol;

                retn += ' - ' + mundipaggRoot.Location.misc.discount + ': ';
                retn += value.discountValue + ' ' + discountLabel;
            }

            retn += '</span><br><br>';
        });

        return retn;

    })(templateSnapshotData,this.formController.mundipaggRoot));

    html = html.replace(/\{plan_installment\}/g,(function(templateSnapshotData){
        if (templateSnapshotData.template.allowInstallments) {
            return '<span class="label label-info">Sim</span>';
        }
        return '<span class="label label-default">Não</span>';
    })(templateSnapshotData));

    configContainer.html(html);
};

OpencartRecurrencyCreationFormModel.prototype
    .getFormElement = function(templateSnapshotData) {
    return $('#form-product');
};

OpencartRecurrencyCreationFormModel.prototype.setPlanPrice = function() {

    var planPrice = 0;

    $('.mundipagg-recurrence-subproduct-price').each(function () {
        parent = $(this).parent().parent().parent();
        quantity = parent.find(".mundipagg-recurrence-subproduct-quantity").val();
        planPrice += parseFloat($(this).val()) * parseInt(quantity);
    });

    planPrice = planPrice.toFixed(2);

    $("#input-price").val(planPrice);
    $(".plan-price").html(planPrice);

    if (planPrice > 0) {
        $(".total-plan-amount").show();
    } else {
        $(".total-plan-amount").hide();
    }
}