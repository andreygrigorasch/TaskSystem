<?php

return [
    'required' => 'Поле «:attribute» обязательно.',
    'present' => 'Передайте поле «:attribute».',
    'email' => 'Укажите корректный адрес электронной почты.',
    'string' => 'Поле «:attribute» должно быть строкой.',
    'integer' => 'Поле «:attribute» должно быть целым числом.',
    'max' => ['string' => 'Поле «:attribute» должно содержать не более :max символов.', 'numeric' => 'Поле «:attribute» не должно превышать :max.'],
    'min' => ['numeric' => 'Поле «:attribute» должно быть не меньше :min.'],
    'exists' => 'Выберите существующего сотрудника.',
    'enum' => 'Недопустимое значение поля «:attribute».',
    'prohibited' => 'Поле «:attribute» нельзя задавать при создании заявки.',
    'attributes' => ['email' => 'электронная почта', 'password' => 'пароль', 'title' => 'название', 'description' => 'описание', 'status' => 'статус', 'version' => 'версия', 'assignee_id' => 'исполнитель', 'page' => 'страница', 'per_page' => 'размер страницы'],
];
