# DocumentManager

DocumentManager предоставляет различные методы, связанные с управлением ресурсами в EvolutionCMS, все действия связанные с документами проходят через эти методы.

## Доступные методы

| метод                      | описание                                  |
| -------------------------- | ----------------------------------------- |
| [ get ](#get)              | получение документа                       |
| [ create ](#create)        | создание документа                        |
| [ edit ](#edit)            | редактирование документа                  |
| [ delete ](#delete)        | удаление документа                        |
| [ undelete ](#undelete)    | восстановление удаленного документа       |
| [ duplicate ](#duplicate)  | копирование документа                     |
| [ setGroups ](#setGroups)  | установка групп документа                 |
| [ publish ](#publish)      | опубликовать документ                     |
| [ unpublish ](#unpublish)  | снять с публикации документ               |
| [ clearCart ](#clearCart)  | очистить корзину с удалёнными документами |
| [ emptyTrash ](#clearCart) | синоним `clearCart`                       |

## Описание методов

### <a name="get"></a> get - получение документа

```php
SiteContent \DocumentManager::get(
    integer $id
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр | описание     |
| -------- | ------------ |
| `$id`    | id документа |

#### Пример получения документа

```php
$id = 1;
$document = \DocumentManager::get($id);

print_r($document->toArray());
```

### <a name="create"></a> create - создание документа

```php
SiteContent \DocumentManager::create(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                                        | обязательно             |
| --------------- | --------------------------------------------------------------- | ----------------------- |
| `$documentData` | массив c полями документа и TV, TV в формате название=>значение | `pagetitle`, `template` |
| `$events`       | вызывать события, связанные с созданием документа               |                         |
| `$cache`        | сбрасывать кэш после создания документа                         |                         |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в `$documentData`    |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие           | передается                                                       |
| ----------------- | ---------------------------------------------------------------- |
| `OnBeforeDocSave` | [ `action` => 'create', `id` => null, `documentData` => &$data ] |
| `OnDocSave`       | [ `action` => 'create', `id` => &$id, `document` => &$document ] |

Параметры:

- `$id` - указатель на переменную с id созданного документа
- `$data` - указатель на массив с данными создаваемого документа
- `$document` - указатель на модель созданного документа

#### Пример создания документа

```php
$document = [
    'pagetitle' => 'New title',
    'template' => 1,
    'test' => 'value', // test - название tv
];

try {
    $document = \DocumentManager::create($document);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="edit"></a> edit - редактирование документа

```php
SiteContent \DocumentManager::edit(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                                        | обязательно |
| --------------- | --------------------------------------------------------------- | ----------- |
| `$documentData` | массив c полями документа и TV, TV в формате название=>значение | `id`        |
| `$events`       | вызывать события связанные с редактированием документа          |             |
| `$cache`        | сбрасывать кэш после редактирования документа                   |             |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в `$documentData`    |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие           | передается                                                                                        |
| ----------------- | ------------------------------------------------------------------------------------------------- |
| `OnBeforeDocSave` | [ `action` => 'update', `id` => &$id, `documentData` => &$data, `document` => &$document_before ] |
| `OnDocSave`       | [ `action` => 'update', `id` => &$id, `document` => &$document_after ]                            |

Параметры:

- `$id` - указатель на переменную с id редактируемого документа
- `$data` - указатель на массив с данными документа
- `$document_before` - указатель на модель документа _до_ редактирования
- `$document_after` - указатель на модель документа _после_ редактирования

#### Пример редактирования документа

```php
$data = [
    'id' => 1,
    'pagetitle' => 'New document title',
    'test' => 'new value' // test - название tv
];

try {
    $document = \DocumentManager::edit($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
     print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="delete"></a> delete - удаление документа

```php
SiteContent \DocumentManager::delete(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                         | обязательно |
| --------------- | ------------------------------------------------ | ----------- |
| `$documentData` | массив содержащий `id` документа                 | `id`        |
| `$events`       | вызывать события связанные с удалением документа |
| `$cache`        | сбрасывать кэш после удаления документа          |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в `$documentData`    |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие             | передается                                                                  |
| ------------------- | --------------------------------------------------------------------------- |
| `OnBeforeDocDelete` | [ `id` => &$id, `document` => &$document_before, `children` => &$children ] |
| `OnDocDelete`       | [ `id` => &$id, `document` => &$document_after, `children` => &$children ]  |

Параметры:

- `$id` - указатель на переменную с id удаляемого документа
- `$document_before` - указатель на модель удаляемого документа _до_ удаления
- `$document_after` - указатель на модель удаляемого документа _после_ удаления
- `$children` - указатель на массив id дочерних удаляемых документов

#### Пример удаления документа

```php
$data = [
    'id' => 1
];

try {
    $document = \DocumentManager::delete($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="undelete"></a> undelete - восстановление удаленного документа

```php
SiteContent \DocumentManager::undelete(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                               | обязательно |
| --------------- | ------------------------------------------------------ | ----------- |
| `$documentData` | массив содержащий id документа                         | `id`        |
| `$events`       | вызывать события связанные с восстановлением документа |             |
| `$cache`        | сбрасывать кэш после восстановления документа          |             |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в `$documentData`    |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие               | передается                                                                  |
| --------------------- | --------------------------------------------------------------------------- |
| `OnBeforeDocUndelete` | [ `id` => &$id, `document` => &$document_before, `children` => &$children ] |
| `OnDocUndelete`       | [ `id` => &$id, `document` => &$document_after, `children` => &$children ]  |

Параметры:

- `$id` - указатель на переменную с id восстанавлиемого документа
- `$document_before` - указатель на модель документа _до_ восстановления
- `$document_after` - указатель на модель документа _после_ восстановления
- `$children` - указатель на массив с id дочерних восстанавливаемых документов

#### Пример восстановления удалённого документа

```php
$data = [
    'id' => 1
];

try {
    $document = \DocumentManager::undelete($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="duplicate"></a>duplicate - дублирование документа

```php
SiteContent \DocumentManager::duplicate(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели созданного дублированием документа SiteContent.

#### Параметры

| параметр        | описание                                            | обязательно |
| --------------- | --------------------------------------------------- | ----------- |
| `$documentData` | массив содержащий id копируемого документа          | `id`        |
| `$events`       | вызывать события связанные с копированием документа |             |
| `$cache`        | сбрасывать кэш после копирования документа          |             |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в $documentData      |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие                | передается                                                               |
| ---------------------- | ------------------------------------------------------------------------ |
| `OnBeforeDocDuplicate` | [ `id` => &$id, `document` => &$document ]                               |
| `OnDocDuplicate`       | [ `id` => &$id, `new_id` => &$new_id, `new_document` => &$new_document ] |

Параметры:

- `$id` - указатель на переменную с id дублируемого документа
- `$document` - указатель на модель дублируемого документа
- `$new_id` - указатель на переменную с id созданного нового документа
- `$new_document` - указатель на модель созданного нового документа

#### Пример дублирования документа

```php
$data = [
    'id' => 1
];

try {
    $document = \DocumentManager::duplicate($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="setGroups"></a> setGroups - назначению документу его группы документа

```php
SiteContent \DocumentManager::setGroups(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                                                | обязательно             |
| --------------- | ----------------------------------------------------------------------- | ----------------------- |
| `$documentData` | массив содержащий id документа и document_groups массив групп документа | `id`, `document_groups` |
| `$events`       | вызываем ли мы события связанные с назначением группы документа         |                         |
| `$cache`        | сбрасываем ли кэш после назначения группы документа                     |                         |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в `$documentData`    |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие                | передается                                                       |
| ---------------------- | ---------------------------------------------------------------- |
| `OnBeforeDocSetGroups` | [ `id` => &$id, `groups` => &$groups, `document` => &$document ] |
| `OnDocSetGroups`       | [ `id` => &$id, `document` => &$document ]                       |

Параметры:

- `$id` - указатель на переменную с id редактируемого документа
- `$groups` - указатель на массив со списком id групп документа
- `$document` - указатель на модель документа

#### Пример назначения группы документов

```php
$data = [
    'id' => 1,
    'document_groups' => [1, 2]
];

try {
    $document = \DocumentManager::setGroups($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="publish"></a>publish - опубликовать документ

```php
SiteContent \DocumentManager::publish(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                               | обязательно |
| --------------- | ------------------------------------------------------ | ----------- |
| `$documentData` | массив содержащий id документа                         | `id`        |
| `$events`       | вызываем ли мы события связанные публикацией документа |             |
| `$cache`        | сбрасываем ли кэш после публикации документа           |             |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в $documentData      |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие              | передается                                        |
| -------------------- | ------------------------------------------------- |
| `OnBeforeDocPublish` | [ `id` => &$id, `document` => &$document_before ] |
| `OnDocPublish`       | [ `id` => &$id, `document` => &$document_after ]  |

Параметры:

- `$id` - указатель на переменную с id публикуемого документа
- `$document_before` - указатель на модель публикуемого документа _до_ публикации
- `$document_after` - указатель на модель публикуемого документа _после_ публикации

#### Пример публикации документа

```php
$data = [
    'id' => 1
];

try {
    $document = \DocumentManager::publish($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="unpublish"></a>unpublish - снять с публикации документ

```php
SiteContent \DocumentManager::unpublish(
    array $documentData,
    bool $events = true,
    bool $cache = true
)
```

#### Возвращает

Объект модели документа SiteContent.

#### Параметры

| параметр        | описание                                                     | обязательно |
| --------------- | ------------------------------------------------------------ | ----------- |
| `$documentData` | массив содержащий `id` документа                             | `id`        |
| `$events`       | вызывать события связанные со снятием с публикации документа |             |
| `$cache`        | сбрасывать кэш после снятия с публикации документа           |             |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в $documentData      |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие                | передается                                        |
| ---------------------- | ------------------------------------------------- |
| `OnBeforeDocUnpublish` | [ `id` => &$id, `document` => &$document_before ] |
| `OnDocUnpublish`       | [ `id` => &$id, `document` => &$document_after ]  |

Параметры:

- `$id` - указатель на переменную с id документа, снимаемого с публикации
- `$document_before` - указатель на модель публикуемого документа _до_ снятия с публикации
- `$document_after` - указатель на модель публикуемого документа _после_ снятия с публикации публикации

#### Пример снятия с публикации документа

```php
$data = [
    'id' => 1
];

try {
    $document = \DocumentManager::unpublish($data);
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```

### <a name="clearCart"></a>clearCart - очистить корзину с удалёнными документами

```php
SiteContent \DocumentManager::clearCart(
    array $documentData = [],
    bool $events = true,
    bool $cache = true
)
```

Существует синоним метода - `emtpyTrash`, вызов метода `DocumentManager::emptyTrash()` с теми же параметрами.

#### Возвращает

Объект модели первого документа SiteContent из оставшихся.

#### Параметры

| параметр        | описание                                    | обязательно |
| --------------- | ------------------------------------------- | ----------- |
| `$documentData` | необязательное поле                         |             |
| `$events`       | вызывать события связанные очисткой корзины |             |
| `$cache`        | сбрасывать кэш после очистки корзины        |             |

#### Исключения

| исключение                                            | описание                                    |
| ----------------------------------------------------- | ------------------------------------------- |
| `\EvolutionCMS\Exceptions\ServiceValidationException` | переданы плохие данные в $documentData      |
| `\EvolutionCMS\Exceptions\ServiceActionException`     | возникла ошибка в процессе обработки данных |

#### События

| событие              | передается         |
| -------------------- | ------------------ |
| `OnBeforeEmptyTrash` | [ `ids` => &$ids ] |
| `OnEmptyTrash`       | [ `ids` => &$ids ] |

Параметры:

- `$ids` - указатель на массив со списком id удалённых документов

#### Пример очистки корзины

```php
try {
    $document = \DocumentManager::clearCart();
} catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
    $validateErrors = $exception->getValidationErrors(); //Получаем все ошибки валидации
    print_r($validateErrors); //Выводим все ошибки валидации
} catch (\EvolutionCMS\Exceptions\ServiceActionException $exception) {
    print_r($exception->getMessage()); //Выводим ошибку процесса обработки данных
}
```
