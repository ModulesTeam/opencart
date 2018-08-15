var PlansCreationController = function (mundipaggRoot,formModelClass)
{
    this.mundipaggRoot = mundipaggRoot;
    this.creationPageFormModel = new formModelClass(this);
    this.templateSnapshop = null;
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
    var data = btoa(JSON.stringify(this.templateSnapshop));
    this.creationPageFormModel.getFormElement()
        .append('' +
            '<input type="hidden" id="mundipagg-template-snapshot-data" ' +
            'name="mundipagg-template-snapshot-data" value="'+data+'" />');
};

PlansCreationController.prototype.showConfigTable = function(templateSnapshop) {
    this.templateSnapshop = templateSnapshop;

    this.creationPageFormModel
        .updateTemplateSnapshotDetailPanel(
            this.templateSnapshop
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
            this.formController.templateSnapshop = null;
            this.getTemplateSnapshotDetailPanelElement().hide();
            this.getAddTemplateFromSelectButtonElement().show();
            this.getTemplateSelectEditPanelElement().show();
        }.bind(this)
    );
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