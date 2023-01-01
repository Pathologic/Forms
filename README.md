# Forms
## Установка
Установить, запустить модуль.

## Настройка
К вызовам сниппета FormLister необходимо добавить параметр prepareProcess со значением saveForm. Сниппет saveForm сохраняет в базу данных поля из формы, которые делятся на основные и дополнительные.
Основные - name, phone, email. Дополнительные указываются разработчиком.

Дополнительные параметры:
* saveFormType - название формы, например "Заявка";
* saveFormFields - дополнительные поля формы в виде массива, где ключ - имя поля в форме, значение - имя под которым поле будет сохранено;
* saveFormDefaults - параметр нужен, если названия основных полей в форме отличаются от name, phone, email. Задается в виде массива, где ключ - имя основного поля, значение - имя поля в форме.

Пример:
```
&saveFormType=`Заявка`
&saveFormFields=`{
    "job":"Место работы",
    "age":"Возраст"
}`
&saveFormDefaults=`{
    "name":"fio",
    "phone":"mobile",
    "email":"user_email"
}`
```