<?php
/**
 *   This file is part of Mobile Assistant Connector.
 *
 *   Mobile Assistant Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Mobile Assistant Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Mobile Assistant Connector. If not, see <http://www.gnu.org/licenses/>.
 */

// Heading
$_['heading_title']       = 'Mobile Assistant Connector';

// Text
$_['text_module']         = 'Модули';
$_['text_success']        = 'Успех: Вы изменили модуль Mobile Assistant Connector!';
$_['button_save_continue']= 'Сохранить и продолжить';
$_['text_edit']           = 'Редактировать';

$_['entry_login']         = 'Логин:';
$_['help_login']          = 'Логин для доступа к модулю Mobile Assistant Connector с вашего мобильного приложения OpenCart Mobile Assistant';

$_['entry_pass']          = 'Пароль:';
$_['help_pass']           = 'Пароль для доступа к модулю Mobile Assistant Connector с вашего мобильного приложения OpenCart Mobile Assistant. По умолчанию - "1". Пожалуйста, измените на собственный';

$_['entry_qr']            = 'QR-код (конфигурация):';
$_['help_qr']             = 'Закодированные URL магазина, логин и пароль к модулю Mobile Assistant Connector. Вы можете просканировать этот код с помощью специального функционала на экране настройки соединения приложения OpenCart Mobile Assistant для автоматического заполнения полей.';

$_['entry_disable_mis_ord_notif'] = 'Отключить уведомления о новых заказах со статусом "Пропавшие заказы"';

$_['entry_status']        = 'Статус:';
$_['module_version']      = 'Версия модуля:';

$_['useful_links']        = 'Полезные ссылки:';
$_['check_new_version']   = 'Проверить новую версию';
$_['submit_ticket']       = 'Создать запрос в службу поддержки';
$_['documentation']       = 'Документация';

// Error
$_['error_permission']    = 'Предупреждение: У вас нет разрешения на изменение настроек этого модуля!';
$_['error_default_cred']  = 'Измените логин и пароль для повышения безопасности!';
$_['error_empty_login']   = "Логин не может быть пустым!";

$_['error_login_details_changed']   = "Детали доступа были изменены. Сохраните изменения, чтобы перегенерировать QR-код";


$_['push_messages_settings'] = "Настройки мгновенных сообщений";
$_['push_messages_settings_help'] = "Пожалуйста, обратите внимание, если вы не удалите настройки на устройстве - оно может быть добавлено еще раз";

$_['device_name']       = "Имя устройства";
$_['account_email']     = "Email аккаунта устройства";
$_['last_activity']     = "Последняя активность";
$_['select_all_none']   = "Выбрать все/ничего";
$_['app_connection_id'] = "ID соединения в аппликации";
$_['store']             = "Магазин";
$_['new_order']         = "Новый заказ";
$_['new_customer']      = "Новый клиент";
$_['order_statuses']    = "Статусы заказа";
$_['currency']          = "Валюта";
$_['status']            = "Статус";
$_['delete']            = "Удалить";
$_['unknown']           = "Неизвестно";

$_['disable']           = "Запретить";
$_['enabled']           = "Разрешено";
$_['enable']            = "Разрешить";
$_['disabled']          = "Запрещено";
$_['are_you_sure']      = "Вы уверены?";
$_['no_data']           = "Нет данных";

$_['bulk_actions']      = "Массовые действия";
$_['enable_selected']   = "Разрешить выбранным";
$_['disable_selected']  = "Запретить выбранным";
$_['delete_selected']   = "Удалить выбранные";

$_['please_select_push_settings'] = "Пожалуйста, выберите настройки";

$_['mac_user']                  = "Пользователь:";
$_['mac_add_user']              = "Добавить пользователя";
$_['mac_get_app_from_gp']       = "Получить приложение с Google Play";
$_['mac_click_or_scan_qr']      = "Нажмите или используйте камеру<br>устройства для чтения QR-кода";
$_['mac_permissions']           = "Разрешения";
$_['mac_regenerate_hash_url']   = "Перегенерировать код для ссылки";
$_['mac_push_notifications']    = "Мгновенные сообщения";
$_['mac_new_order_created']     = "Создан новый заказ";
$_['mac_order_status_changed']  = "Изменен статус заказа";
$_['mac_new_customer_created']  = "Создан новый пользователь";
$_['mac_store_statistics']      = "Статистика магазина";
$_['mac_view_store_statistics'] = "Просмотр статистики магазина";
$_['mac_orders']                = "Заказы";
$_['mac_view_order_list']       = "Просмотр списка заказов";
$_['mac_view_order_details']    = "Просмотр деталей заказа";
$_['mac_change_order_status']   = "Изменение статуса заказа";
$_['mac_order_picking']         = "Комплектование заказа";
$_['mac_customers']             = "Клиенты";
$_['mac_view_customer_list']    = "Просмотр списка клиентов";
$_['mac_view_customer_details'] = "Просмотр деталей клиента";
$_['mac_products']              = "Продукты";
$_['mac_view_product_list']     = "Просмотр списка продуктов";
$_['mac_view_product_details']  = "Просмотр деталей продукта";
$_['mac_view_product_edit']     = "Редактирование продуктов";
$_['mac_view_product_add']      = "Создание продуктов";
$_['mac_all']                   = "Все";
$_['mac_not_set']               = "Не задано";
$_['mac_base_currency']         = "Базовая валюта";
?>