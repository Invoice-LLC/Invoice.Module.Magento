<h1>Invoice Magento module</h1>

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.WooCommerce/archive/master.zip) и скопируйте содержимое архива в корень сайта
2. Зайдите в консоль, перейдите в папку с сайтом, затем введите команды:
```bash
	php bin/magento module:enable Magento_InvoicePayment
	php bin/magento setup:upgrade
	php bin/magento setup:di:compile
	php bin/magento setup:static-content:deploy -f
```
3. Перейдите во вкладку **Stores->Configuration**<br>
![Imgur](https://imgur.com/B1F1wNO.png)
4. Перейдите во вкладку **Sales->Payment methods->Платежная система Invoice** и введите ваши данные<br> 
![Imgur](https://imgur.com/LRgRt6C.png)
(Все данные вы можете получить в [личном кабинете Invoice](https://lk.invoice.su/))
5. Затем перейдите во вкладку **General->General->Store information** и введите название магазина(С таким названием будет создан терминал)<br>
![Imgur](https://imgur.com/DV7vYHv.png)
6. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/invoice/callback/index**
![Imgur](https://imgur.com/LZEozhf.png)