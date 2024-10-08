## Установка

1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/weborsha/currencies.git
   cd currencies

2. Запустите Docker контейнеры:
    ```bash
    docker-compose up -d
   
3. Перейдите в директорию app и установите зависимости:
    ```bash
    cd app
    docker-compose run --rm composer install

4. Примените миграции:
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate

5. Очистите кэш:
   ```bash
   docker-compose exec php php bin/console cache:clear
