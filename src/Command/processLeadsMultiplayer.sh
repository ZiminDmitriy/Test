#!/bin/bash

mkdir -p ./var && touch ./var/commands.log && chmod -R 777 ./var/commands.log

QUANTITY_OF_CONSUMERS=50

FLAG=false

for (( i=1; i<=$QUANTITY_OF_CONSUMERS; i++ ))
do
  if !(php ./src/Command/processLeads.php  >> ./var/commands.log &);
  then
    FLAG=true
  fi
done

if [ !FLAG ];
then
  echo 'Processing leads.'
else
  echo 'Any errors were found'
fi
