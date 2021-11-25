# Vires.Finance аудит безопасности

**По-русски** | [In english](README.md)

- [Область исследования](viresfinance-security-audit.ru.md#область-исследования)
- [Состав проекта](viresfinance-security-audit.ru.md#состав-проекта)
- [Безопасность](viresfinance-security-audit.ru.md#безопасность)
  - [Общее](viresfinance-security-audit.ru.md#общее)
  - [Администрирование](viresfinance-security-audit.ru.md#администрирование)
- [Архитектура](viresfinance-security-audit.ru.md#архитектура)
  - [RESERVE](viresfinance-security-audit.ru.md#reserve)
  - [MAIN](viresfinance-security-audit.ru.md#main)
  - [SETTINGS](viresfinance-security-audit.ru.md#settings)
- [Модель угроз](viresfinance-security-audit.ru.md#модель-угроз)
  - [Атаки на аргументы](viresfinance-security-audit.ru.md#атаки-на-аргументы)
  - [Атаки амплификации](viresfinance-security-audit.ru.md#атаки-амплификации)
- [Прочие рекомендации](viresfinance-security-audit.ru.md#прочие-рекомендации)
- [Выводы](viresfinance-security-audit.ru.md#выводы)

# Вспомогательные скрипты
- Скрипты расположены в папке [test](test) данного репозитория
- Скрипты основаны на функциональности [WavesKit](https://github.com/deemru/WavesKit)
- Используйте `composer install` для установки зависимостей
- Рекомендуется использовать локальную ноду Waves с REST API (по умолчанию это 127.0.0.1:6869)
- Вы можете запускать скрипты в любом порядке
