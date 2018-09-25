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
    'configure_instructions' => 'Instruções de pagamento',
    'configure_checkout_instructions' => 'Instruções de checkout'
];
// -----------------------------------------------------------------

$_['boletoCreditCard'] = [
    'label' => 'Boleto + Cartão de Crédito',
    'configure_section' => 'Configuração do boleto + cartão de crédito',
    'configure_payment_title' => 'Título do pagamento',
    'configure_name' => "Nome",
    'configure_select_bank' => 'Banco',
    'configure_due_at' => 'Número de dias do vencimento do boleto após a data da compra',
    'configure_instructions' => 'Instruções de pagamento',
    'configure_checkout_instructions' => 'Instruções de checkout'
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

// Charge screen
$_['charge_screen'] = [
    'charge' => 'Charge',
    'charge_action' => 'Charge %s',
    'heading_title' => 'Mundipagg Charge Actions',
    'charge_information' => 'Charge information',
    'order' => 'Order',
    'product' => 'Product',
    'model' => 'Model',
    'quantity' => 'Quantity',
    'unit_price' => 'Price',
    'total' => 'Total',
    'status' => 'Status',
    'charge_id' => 'Charge id',
    'payment_method' => 'Payment method',
    'amount' => 'Amount',
    'actions' => 'Actions',
    'paid_amount' => 'Paid amount',
    'canceled_amount' => 'Canceled amount',
    'capture' => 'capture',
    'cancel' => 'cancel',
    'amount' => 'Amount',
    'payment_method' => 'Payment method',
    'how_do_you_want' => 'How do you want to %s this charge?',
    'total_action' => 'Total %s',
    'partial_action' => 'Partial %s',
    'close' => 'Close',
    'charge_capture' => 'Charge capture',
    'charge_cancel' => 'Charge cancel',
    'charge_action_success' => 'Charge updated successfully'
];

// Recurrence
$_['mundipagg'] = [
    'misc' => [
        'of' => 'de',
        'discount' => 'desconto'
    ],
    'recurrence' => [
        'template' => [
            'due' => [
                'type' => [
                    \Mundipagg\Aggregates\Template\DueValueObject::TYPE_EXACT => [
                        'label' => 'Todo dia %d',
                        'name' => 'Dia exato'
                    ],
                    \Mundipagg\Aggregates\Template\DueValueObject::TYPE_PREPAID => [
                        'label' => 'Pré-pago',
                        'name' => 'Pré-pago'
                    ],
                    \Mundipagg\Aggregates\Template\DueValueObject::TYPE_POSTPAID => [
                        'label' => 'Pós-pago',
                        'name' => 'Pós-pago'
                    ]
                ]
            ],
            'repetition' => [
                'cycle' => [
                    'label' => ['ciclo','ciclos']
                ],
                'discount' => [
                    'type' => [
                        \Mundipagg\Aggregates\Template\RepetitionValueObject::DISCOUNT_TYPE_FIXED => [
                            'label' => "%s%.2f",
                            'symbol' => "R$"
                        ],
                        \Mundipagg\Aggregates\Template\RepetitionValueObject::DISCOUNT_TYPE_PERCENT => [
                            'label' => "%s%.2f%%",
                            'symbol' => "%"
                        ]
                    ]
                ],
                'interval' => [
                    'type' => [
                        \Mundipagg\Aggregates\Template\RepetitionValueObject::INTERVAL_TYPE_WEEKLY => [
                            'label' => ['semana','semanas'],
                            'name' => 'Semanalmente'
                        ],
                        \Mundipagg\Aggregates\Template\RepetitionValueObject::INTERVAL_TYPE_MONTHLY => [
                            'label' => ['mês','meses'],
                            'name' => 'Mensalmente'
                        ],
                        \Mundipagg\Aggregates\Template\RepetitionValueObject::INTERVAL_TYPE_YEARLY => [
                            'label' => ['ano','anos'],
                            'name' => 'Anualmente'
                        ]
                    ]
                ]
            ]
        ]
    ]
];