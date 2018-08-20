<?php
require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

// Heading
$_['heading_title'] = 'MundiPagg';
$_['text_mundipagg'] = '<a href="https://github.com/mundipagg/opencart" target="_blank"><img src="/admin/view/image/mundipagg/mundipagg.png" alt="MundiPagg" title="MundiPagg" style="border: 1px solid #EEEEEE;" /></a>';
// -----------------------------------------------------------------

// General tab settings
$_['general'] = [
    'label' => 'Geral',
    'payment_title' => 'Título do pagamento',
    'mapping_section' => 'Mapeamento de campos',
    'mapping_number' => 'Número',
    'mapping_complement' => 'Complemento',
    'api_section' => 'Dados da Api',
    'api_prod_key' => 'Secret key [produção]',
    'api_test_key' => 'Secret key [teste]',
    'api_prod_pub_key' => 'Public key [produção]',
    'api_test_pub_key' => 'Public key [teste]',
    'api_test_mode' => 'Modo teste',
    'module_section' => 'Módulo',
    'log_section' => 'Log',
    'select' => 'Selecione'
];
// -----------------------------------------------------------------

$_['credit_card'] = [
    'label' => 'Cartão de crédito',
    'configure_section' => 'Configuração de cartões',
    'configure_payment_title' => 'Título do pagamento',
    'configure_invoice_name' => "Nome na fatura",
    'configure_operation_type' => 'Tipo da operação',
    'configure_is_saved_enabled' => 'Habilitar cartões salvos',
    'configure_enable_two_credit_cards' => 'Habilitar 2 cartões',
    'configure_two_credit_cards_payment_title' => 'Título do pagamento para 2 cartões',
    'configure_auth_label' => 'Autorizar',
    'configure_auth_capture_label' => 'Autorizar e capturar',
    'manage_section' => 'Gerenciar cartões',
    'manage_remove_button' => 'Excluir',
    'manage_add_card' => 'Adicionar outro cartão',
    'brand_name' => 'Nome da bandeira',
    'enable' => 'Habilitar',
    'max_installments' => 'Número máximo de parcelas',
    'installments_without_interest' => 'Número máximo de parcelas sem juros',
    'initial_interest' => 'Juros iniciais',
    'incremental_interest' => 'Juros incrementais'
];
// -----------------------------------------------------------------

$_['boleto'] = [
    'label' => 'Boleto',
    'configure_section' => 'Configuração do boleto',
    'configure_payment_title' => 'Título do pagamento',
    'configure_name' => "Nome",
    'configure_select_bank' => 'Banco',
    'configure_due_at' => 'Número de dias do vencimento do boleto após a data da compra',
    'configure_instructions' => 'Instruções de pagamento'
];
// -----------------------------------------------------------------

$_['boletoCreditCard'] = [
    'label' => 'Boleto + Cartão de Crédito',
    'configure_section' => 'Configuração do boleto + cartão de crédito',
    'configure_payment_title' => 'Título do pagamento',
    'configure_name' => "Nome",
    'configure_select_bank' => 'Banco',
    'configure_due_at' => 'Número de dias do vencimento do boleto após a data da compra',
    'configure_instructions' => 'Instruções de pagamento'
];
// -----------------------------------------------------------------


// -----------------------------------------------------------------

$_['antifraud'] = [
    'label' => 'Anti fraude',
    'configure_section' => 'Configuração de anti fraude',
    'configure_minval' => 'Valor mínimo do pedido'
];
// -----------------------------------------------------------------

$_['logs'] = [
    'label' => 'Logs',
    'title' => 'Logs'
];
// -----------------------------------------------------------------

$_['misc'] = [
    'yes' => 'Sim',
    'no' => 'Não',
    'on' => 'Ligado',
    'off' => 'Desligado',
    'enabled' => 'Habilitado',
    'button_save' => 'Salvar',
    'button_cancel' => 'Cancelar',
    'success' => 'Mundipagg options saved!'
];

// Mundipagg Admin Menu
$_['admin_menu'] = [
    'Settings' => 'Configurações',
    'Subscriptions' => 'Assinaturas',
    'Plans' => 'Planos',
    'create' => 'adcionar'
];
// -----------------------------------------------------------------

// Recurrence
$_['recurrence'] = [
    'Settings' => 'Configurações',
    'Subscriptions' => 'Assinaturas',
    'Plans' => 'Planos',
    'create' => 'adcionar',
    'title' => 'Recurrence',
    'subscriptionInstallment_title' => 'Enable Subscription Installment',
    'creditcardUpdateCustomer_title' => 'Enable Credit Card update by customer',
    'paymentUpdateCustomer_title' => 'Enable payment method update by customer',
    'subscriptionByPlan_title' => 'Enable Subscription by plan',
    'singleSubscription_title' => 'Enable Single Subscription',
    'checkoutConflictMessage_title' => 'Checkout Conflict Message',
];
// -----------------------------------------------------------------
