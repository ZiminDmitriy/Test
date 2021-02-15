# Deployment | Launch
1) This command create full environment. In "_ROOT_/_docker" directory:
   docker-compose up -d --build
2) This command downloads dependencies:
   docker exec test_php-cli /bin/bash -c "composer install --no-interaction --no-scripts"
3) This command starts publisher (AMQP context):
   docker exec test_php-cli /bin/bash -c "php ./src/Command/generateLeads.php"
4) This command starts consumers (AMQP context):
   docker exec test_php-cli /bin/bash -c "bash ./src/Command/processLeadsMultiplayer.sh"
   
# Buisness-processes
To disable processing a Category, you should add category name to .env file to "DISABLED_LEAD_CATEGORIES" parameter.
