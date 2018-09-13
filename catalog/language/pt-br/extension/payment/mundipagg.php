<?php
$_['boleto'] = [
    'payment_method_name' => 'Boleto',
    'title' => 'Pagamento via boleto',
    'click_to_generate' => 'Clique aqui para gerar seu boleto.',
    'click_to_follow' => '<a href="%s" target="_blank">Clique aqui</a> para exibir seu boleto.',
    'pending_order_status' => 'Pedido pendente - Aguardando pagamento do boleto.',
    'instructions' => "Após clicar em 'Clique aqui para gerar seu boleto' "
        . "você será redirecionado para uma nova janela "
        . "e poderá fazer o donwload ou a impressão de seu boleto."
];

$_['credit_card'] = [
    'payment_method_name' => 'Cartão de crédito',
    'credit_card_number' => 'Número do cartão',
    'valid_thru' => 'Validade',
    'brand' => 'Bandeira',
    'holder_name' => 'Nome impresso no cartão',
    'cvv' => 'CVV',
    'no_brands_enabled' => 'Nenhuma bandeira habilitada',
    'installments' => 'Parcelas',
    'without_interest' => 'sem juros',
    'interest' => 'juros: ',
    'new_credit_card' => 'Novo cartão',
    'save_creditcard_message' => 'Salvar este cartão para compras futuras'
];

$_['order'] = [
    'pending' => 'Pedido pendente',
    'paid' => 'Pedido pago',
    'cancel' => 'Pedido cancelado',
    'void' => 'Pedido cancelado',
];

$_['order_history_update'] = [
    'chargePaid' => 'Cobrança paga: ',
    'chargeRefunded' => 'Cobrança estornada',
    'chargeOverPaid' => 'Cobrança paga à maior: ',
    'chargeUnderPaid' => 'Cobrança paga à menor: ',
    'chargePaymentFailed' => 'Cobrança com falha no pagamento',
    'orderPaid' => 'Pedido pago ',
    'orderCanceled' => 'Pedido cancelado ',
    'of' => ' de ',
    'chargePartialCanceled' => 'Cobrança parcialmente cancelada'
];

$_['misc'] = [
    'no_payment_methods_enabled' => 'Nenhum método de pagamento habilitado',
    'continue' => 'Continuar'
];

$_['error_message'] = [
    'unknown_order_status' => 'Ops! Something went wrong. Try again in a few minutes or contact the store administration'
];

$_['saved_creditcard'] = [
    'title' => 'Meus cartões',
    'my_creditcards' => 'Meus cartões',
    'account' => 'Conta',
    'your_saved_creditcards' => 'Meus cartões salvos.',
    'saved_card_confirm_delete' => 'Esse cartão será removido. Você deseja continuar?',
    'delete_card_wait_message' => 'Aguarde...',
    'delete_card_button' => 'Excluir',
    'brand' => 'Bandeira',
    'last_four_digits' => 'Últimos quatro dígitos',
    'delete' => 'Excluir'
];

$_['order_statuses'] = [
    'pending' => 'Pedido criado',
    'paid' => 'Pagamento efetuado',
    'canceled' => 'Pedido cancelado',
    'failed' => 'Falha no pagamento'
];

$_['account_info'] = [
    'text_payment_data' => 'Dados de Pagamento',
    'column_payment_method' => 'Método de pagamento',
    'column_status' => 'Status',
    'column_paid_amount' => 'Quantia paga',
    'column_amount' => 'Quantia Total',

    'column_boleto_link' => 'Link',
    'column_boleto_line_code' => 'Código de barras',
    'column_boleto_due_at' => 'Vencimento',

    'column_creditcard_holder_name' => 'Nome do titular',
    'column_creditcard_brand' => 'Bandeira',
    'column_creditcard_number' => 'Numero do cartão',
    'column_creditcard_installments' => 'Parcelas',

    'boleto_link_message' => 'Clique aqui para exibir seu boleto',
    'text_no_results' => 'Sem resultados.'
];