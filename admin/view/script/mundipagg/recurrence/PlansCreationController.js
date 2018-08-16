var PlansCreationController = function (mundipaggRoot,formModelClass)
{
    this.mundipaggRoot = mundipaggRoot;
    this.creationPageFormModel = new formModelClass(this);
    this.templateSnapshot = null;
};

PlansCreationController.prototype.init = function() {
    this.creationPageFormModel.init();
    if (typeof MundipaggRecurrencyFormData !== "undefined") {
        this.showConfigTable(MundipaggRecurrencyFormData);
    }
};
PlansCreationController.prototype.removeTemplateSnapShotDataFromForm = function() {
    this.creationPageFormModel.getFormElement()
        .find('#mundipagg-template-snapshot-data').remove();
};
PlansCreationController.prototype.addTemplateSnapShotDataToForm = function() {
    var data = btoa(JSON.stringify(this.templateSnapshot));
    this.creationPageFormModel.getFormElement()
        .append('' +
            '<input type="hidden" id="mundipagg-template-snapshot-data" ' +
            'name="mundipagg-template-snapshot-data" value="'+data+'" />');
};

PlansCreationController.prototype.showConfigTable = function(templateSnapshot) {
    this.templateSnapshot = templateSnapshot;

    this.creationPageFormModel
        .updateTemplateSnapshotDetailPanel(
            this.templateSnapshot
        );

    this.creationPageFormModel
        .getTemplateSnapshotDetailPanelElement()
        .show();
    this.creationPageFormModel
        .getAddTemplateFromSelectButtonElement()
        .hide();
    this.creationPageFormModel
        .getTemplateSelectEditPanelElement()
        .hide();

    this.addTemplateSnapShotDataToForm();
};

PlansCreationController.prototype.addTemplateFromSelect = function() {
    var templateInfoUrl = this.creationPageFormModel.getTemplateInfoUrl();
    var selectObj = this.creationPageFormModel.getTemplateSelectElement();
    var selectedElementId = selectObj.val();

    this.creationPageFormModel
        .getAddTemplateFromSelectButtonElement()
        .prop( "disabled", true )
        .find("i")
        .addClass('fa-cog fa-spin')
        .removeClass('fa-plus-circle');

    this.creationPageFormModel
        .getTemplateSelectElement()
        .prop( "disabled", true );

    this.removeTemplateSnapShotDataFromForm();

    $.ajax({
        url: templateInfoUrl + "&template_id=" + selectedElementId,
        success: this.showConfigTable.bind(this),
        error: function(result) {
            console.log("error",result.responseJSON);
        },
        complete: function() {
            this.creationPageFormModel
                .getAddTemplateFromSelectButtonElement()
                .prop( "disabled", false)
                .find("i")
                .addClass('fa-plus-circle')
                .removeClass('fa-cog fa-spin');

            this.creationPageFormModel
                .getTemplateSelectElement()
                .prop( "disabled", false );
        }.bind(this)
    });
};

var OpencartRecurrencyCreationFormModel = function(formController) {
  this.formController = formController;
};

OpencartRecurrencyCreationFormModel.prototype.init = function() {
    var _self = this;
    //replace form action
    var formAction = $('#form-product').attr('action');
    var splitData = formAction.split("?");
    var baseUrl = splitData[0];
    splitData = splitData[1].split("&");
    splitData[0] = 'route=extension/payment/mundipagg/plans';
    var finalUrl = baseUrl + '?' + splitData.join("&") + "&action=save&mundipagg_plan";
    $('#form-product').attr('action',finalUrl);

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
                trial: $('#mp-recurrency-trial').val()
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
                thumb: ui.item.thumb
            });
            $('#mp-recurrence-product-search').val('');
        }.bind(_self)
    };
    $('#mp-recurrence-product-search').autocomplete(autocompleteOptions);
};

OpencartRecurrencyCreationFormModel.prototype
    .addProductToPlan = function(productData) {
    var html = $('#mp-recurrence-product-row-template').html();
    html = html.replace(/\{product_id\}/g,productData.id);
    html = html.replace(/\{product_name\}/g,productData.name);
    html = html.replace(/\{product_thumb\}/g,productData.thumb);

    var selectOptions = '';
    var intervalLocation = this.formController.mundipaggRoot.Location.recurrence.template.repetition.interval.type;
    Object.keys(intervalLocation).forEach(function(type){
        selectOptions += '<option value="'+type+'">'+intervalLocation[type].label[1]+'</option>';
    });
    html = html.replace(/\{product_select_options\}/g,selectOptions);

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
    $('#mp-recurrency-description').val(templateSnapshot.template.description);
    $('#mp-recurrency-name').val(templateSnapshot.template.name);
    $('#mp-recurrency-trial').val(templateSnapshot.template.trial);
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
    return $('.template-select-edit-panel');
};

OpencartRecurrencyCreationFormModel.prototype
    .getTemplateInfoUrl = function() {
    return this.templateInfoUrl;
};

OpencartRecurrencyCreationFormModel.prototype
    .updateTemplateSnapshotDetailPanel = function(templateSnapshotData) {
    var tds = this.getTemplateSnapshotDetailPanelElement()
        .find('table#images tbody td');
    $(tds[0]).html((function(templateSnapshotData){
        var retn = templateSnapshotData.template.name;
        retn += ' <span class="label label-primary">plan</span>';
        if (templateSnapshotData.template.trial > 0) {
            retn += ' <span class="label label-warning">' +
                    templateSnapshotData.template.trial +
                    ' day trial</span>';
        }
        return retn;
    })(templateSnapshotData));
    $(tds[1]).html(templateSnapshotData.template.description);
    $(tds[2]).html((function(templateSnapshotData){
        var retn = "";
        if (templateSnapshotData.template.acceptBoleto) {
            retn += "<span class='label label-default'>Boleto</span> ";
        }
        if (templateSnapshotData.template.acceptCreditCard) {
            retn += "<span class='label label-default'>Cartão de Crédito</span> ";
        }
        return retn;
    })(templateSnapshotData));
    $(tds[3]).html((function(templateSnapshotData,mundipaggRoot){
        var retn = mundipaggRoot.Location
            .recurrence.template.due
            .type[templateSnapshotData.dueAt.type].label;
        retn = retn.replace('%d',templateSnapshotData.dueAt.value);

        return "<span class='label label-default'>" + retn + "</span>";

    })(templateSnapshotData,this.formController.mundipaggRoot));

    $(tds[4]).html((function(templateSnapshotData,mundipaggRoot){

        var intervalLabel = mundipaggRoot.Location
            .recurrence.template.repetition
            .interval.type[templateSnapshotData.repetitions[0].intervalType].label;
        intervalLabel = intervalLabel[
            templateSnapshotData.repetitions[0].frequency > 1 ? 1 : 0
        ];

        var retn = '<span class="label label-default">' +
            templateSnapshotData.repetitions[0].cycles +
            ' cycles</span> ';
        retn += '<span class="label label-default">' +
            templateSnapshotData.repetitions[0].frequency + ' ' + intervalLabel +
            '</span> '
        return retn;
    })(templateSnapshotData,this.formController.mundipaggRoot));

    $(tds[5]).html((function(templateSnapshotData){
        if (templateSnapshotData.template.allowInstallments) {
            return '<span class="label label-info">Sim</span>';
        }
        return '<span class="label label-default">Não</span>';
    })(templateSnapshotData));
};

OpencartRecurrencyCreationFormModel.prototype
    .getFormElement = function(templateSnapshotData) {
    return $('#form-product');
};