<?php

namespace App\Services;

use Data\lang\Lang;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use Src\Model\DataMapper\DataMapper;

class TRONWALLET {
    private $tron;
    private $db;
    private $sysconfig;
    private $ID;
    private $api_key;

    function __construct($ID=0,$api_key='') {
        global $db, $sysconfig;
        $this->db = $db;
        $this->sysconfig = $sysconfig;
        $this->ID = $ID;
        $this->api_key = $api_key;

        $fullNode = new HttpProvider('http://65.108.233.218:8090');
        $solidityNode = new HttpProvider('http://65.108.233.218:8091');
        $eventServer = new HttpProvider('http://65.108.233.218:8090');

        try {
            $this->tron = new Tron($fullNode, $solidityNode, $eventServer);
        } catch (TronException $e) {
            exit($e->getMessage());
        }
    }

    public function generateAddress(): \IEXBase\TronAPI\TronAddress {
        return $this->tron->createAccount();
    }

    public function getTrxBalance(string $address): string {
        return $this->tron->getBalance($address, true);
    }

    public function getUsdtBalance(string $address): string {
        $contract = $this->getUsdtContract();
        return $contract->balanceOf($address);
    }

    public function transferTrx(string $fromAddress, string $fromPrivateKey, string $destinationAddress, float $amount) {
        $this->tron->setAddress($fromAddress);
        $this->tron->setPrivateKey($fromPrivateKey);

        return $this->tron->send($destinationAddress, $amount);
    }

    public function transferUsdt(string $fromAddress, string $fromPrivateKey, string $destinationAddress, float $amount) {
        $this->tron->setAddress($fromAddress);
        $this->tron->setPrivateKey($fromPrivateKey);

        $contract = $this->getUsdtContract();
        //var_dump($contract->transfer($destinationAddress, $amount));
        return $contract->transfer($destinationAddress, $amount);
    }

    private function getUsdtContract(): \IEXBase\TronAPI\TRC20Contract {
        return $this->tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
    }

    public function transferUsdtWithFeeLimit(string $fromAddress, string $fromPrivateKey, string $destinationAddress, float $amount, int $feeLimit) {
        $this->tron->setAddress($fromAddress);
        $this->tron->setPrivateKey($fromPrivateKey);
        $contract = $this->getUsdtContract();
        return $contract->transfer($destinationAddress, $amount)->send(['feeLimit' => $feeLimit]);
    }

    function getOrCreateWallet(int $id_user){
        // Проверяем наличие кошелька в базе данных со status = 1
        $stmt = $this->db->prepare("SELECT `wallet` FROM `wallet_pay` WHERE `id_user` = ? AND `status` = '1' LIMIT 1");
        $stmt->bind_param('i', $id_user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Кошелек найден, возвращаем его
            $wallet = $result->fetch_assoc()['wallet'];
            $info = [
                [
                    'wallet' => $wallet,
                    'QR' => 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $wallet,
                    'time' => time()
                ]
            ];
        } else {
            // Кошелек не найден, создаем новый
            $this->CreateNewAddress($id_user);
            return; // Останавливаем выполнение функции, так как CreateNewAddress уже вывела информацию в формате JSON
        }

        header('Content-Type: application/json');
        echo json_encode($info, JSON_UNESCAPED_UNICODE);
    }

    function getUserTronAddress($id_user = ''){
        $result = $this->db->query("SELECT `wallet`, `privat_key` FROM `wallet_pay` WHERE `id_user`='$id_user' AND `status`='1' LIMIT 1");
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return 0;
        }
    }

    function CreateNewAddress($id_user = ''){
        $address = $this->generateAddress();

        //$public_key = $address->getPublicKey();
        $private_key = $address->getPrivateKey();
        $address_base58 = $address->getAddress(true);

        $stmt = $this->db->prepare("INSERT INTO `wallet_pay`(`id_user`, `wallet`, `privat_key`, `status`, `time`) VALUES (?, ?, ?, '1', ?)");
        $currentTime = time();
        $stmt->bind_param('sssi', $id_user, $address_base58, $private_key, $currentTime);

        if ($stmt->execute()) {
            $info = [
                [
                    'wallet' => $address_base58,
                    'QR' => 'https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $address_base58,
                    'time' => $currentTime
                ]
            ];

            header('Content-Type: application/json');
            echo json_encode($info, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode('что-то пошло не так', JSON_UNESCAPED_UNICODE);
        }
    }

    public function executeTransfers(string $address, string $address_api, string $address2, string $address2_api, string $address3) {
        // Проверка баланса адреса $address в USDT
        $amount = $this->getUsdtBalance($address);
        $id_balance = 1;
        $proc = 3;
        $limit = 1;
        $id_plan = 1;
        $id_investments = 0;
        $trx = 40;

        //echo $amount;
        if ($amount >= $limit) {
            // Проверка баланса адреса $address2 в TRX
            $balance2 = $this->getTrxBalance($address2);



            if ($balance2 >= $trx) {
                // Если баланс $address2 больше или равен 10, перевести 10 TRX с адреса $address2 на адрес $address
                $transferTrxResult = $this->transferTrx($address2, $address2_api, $address, $trx);
                // Вывести результат перевода TRX var_dump($transferUsdtResult);
            } else {
                return ['error' => 1, 'text' => 'The TRX balance of address2 is not sufficient for the transfer.'];
            }

            // Перевести всю сумму с баланса $address на $address3 в USDT
            $transferUsdtResult = $this->transferUsdt($address, $address_api, $address3, $amount);
            // Вывести результат перевода USDT var_dump($transferUsdtResult);

            //$transferUsdtResult = $this->transferUsdtWithFeeLimit($address, $address_api, $address3, $amount, 20000000);
            //var_dump($transferUsdtResult);
            if ($transferUsdtResult['result'] == true) {

                $id_stat = $this->MoneyForProc($this->ID, $amount, $id_balance, 'plus', $proc, $transferUsdtResult['txid'], $id_plan, 0, $transferUsdtResult['txid']);
                if($id_stat>0){
                    return ['error' => 0, 'text' => 'Balance replenished successfully'];
                }else{
                    file_put_contents('/ERROR-'.$this->ID.'.txt', print_r( $this->ID.' - '.$amount, true ) );
                }
            }else{
                file_put_contents( __DIR__.'/ERROR-'.$this->ID.'.txt', print_r( $this->ID.' - '.$amount, true ) );
            }

        } else {
            return ['error' => 1, 'text' => 'The USDT balance of the address is not sufficient for the transfer.'];
        }
    }


    public function withdrawUsdt(string $fromAddress, string $fromPrivateKey)
    {
        $conn = $this->db;

        $conn->begin_transaction(); // Начинаем транзакцию

        try {
            // Fetch order
            $stmt = $conn->prepare("SELECT * FROM `auto_payout` WHERE `status` = 0 LIMIT 1");
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            if (!$order) {
                $conn->rollback();
                return 'Заявок на выплату больше нет!'; // No order to process
            }

            // Fetch withdraw information
            $stmt = $conn->prepare("SELECT * FROM `withdraw` WHERE `id` = ? AND `status` = 1 LIMIT 1");
            $stmt->bind_param("i", $order['id_withdraw']);
            $stmt->execute();
            $withdraw = $stmt->get_result()->fetch_assoc();
            if (!$withdraw) {
                $conn->rollback();
                return; // No withdrawal record found
            }

            $sum = sprintf("%01.2f", $withdraw['sum']);
            $transferUsdtResult = $this->transferUsdt($fromAddress, $fromPrivateKey, $withdraw['wallet'], $sum);

            if ($transferUsdtResult['result'] === true) {
                $time = time();

                // Update auto_payout
                $stmt = $conn->prepare("UPDATE `auto_payout` SET `completed_time` = ?, `status` = 1, `comment` = 'cron', `payment_num` = ? WHERE `id` = ? AND `status` = 0");
                $stmt->bind_param("isi", $time, $transferUsdtResult['txid'], $order['id']);
                $stmt->execute();

                // Update withdraw
                $stmt = $conn->prepare("UPDATE `withdraw` SET `time_confirm` = ?,`status`=2 WHERE `id` = ?");
                $stmt->bind_param("ii", $time, $withdraw['id']);
                $stmt->execute();

                // Update stat
                $stmt = $conn->prepare("UPDATE `stat` SET `status` = 1, `num_payment` = ?, `comment` = ? WHERE `id` = ? AND `status` = 0");
                $stmt->bind_param("ssi", $transferUsdtResult['txid'], $transferUsdtResult['txid'], $withdraw['id_stat']);
                $stmt->execute();

                $conn->commit(); // Применяем изменения
                echo 'Выплачено: ID '.$withdraw['id_user'].' - txId: '.$transferUsdtResult['txid'];
            } else {
                $conn->rollback(); // Откатываем изменения
                file_put_contents(__DIR__ . '/ERROR-' . $withdraw['id_user'] . '.txt', $withdraw['id'] . ' - ' . $sum);
            }
        } catch (Exception $e) {
            $conn->rollback(); // Откатываем изменения в случае ошибки
            throw $e; // Повторно выбрасываем ошибку
        }
    }


    /**
     * @param string $fromAddress
     * @param string $fromPrivateKey
     * @param int $autoPayoutId
     * @param DataMapper $dataMapper
     * @return array|string[]
     */
    public function withdrawMoneyInUSDT(
        string $fromAddress, string $fromPrivateKey,
        int $autoPayoutId, DataMapper $dataMapper
    ) {
        $unprocessedStatus = 0;
        $completedStatus = 1;
        $paidStatus = 2;

        /**
         * Fetch withdraw wallet and sum
         *
         * [
         *      id =>
         *      email =>
         *      id_stat =>
         *      sum =>
         *      wallet =>
         * ]
         */
        $withdraw = $dataMapper->selectWithdrawByAutoPayoutId($autoPayoutId);
        if($withdraw === false) {
            return [
                'error' => 'Произошла ошибка при получении записи заявки на выплату!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }

        /**
         * Process Payment through the Tron Network
         */
        $sum = sprintf("%01.2f", $withdraw['sum']);
        $transferUSDT = $this->transferUsdt(
            $fromAddress, $fromPrivateKey, $withdraw['wallet'], $sum
        );
        if ($transferUSDT['result'] !== true) {

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка при отправке выплаты!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }

        /**
         * Put the value of transaction hash into separate variable
         */
        $transactionHash = $transferUSDT['txid'];


        /**
         * Begin Transaction
         */
        $dataMapper->transactionBegin();

        /**
         * Update auto payout record - completed_time, status = 1, payment_num, comment
         */
        $updatedAutoPayout = $dataMapper->updateAutoPayoutById(
            $autoPayoutId, $transactionHash, $completedStatus, 'cron'
        );
        if($updatedAutoPayout === false) {
            $dataMapper->transactionRollback();

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка в процессе обновления записи автовыплати!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }


        /**
         * Update withdraw status - time_confirm, status = 2
         */
        $updatedWithdraw = $dataMapper->updateWithdrawById(
            $withdraw['id'], $paidStatus
        );
        if($updatedWithdraw === false) {
            $dataMapper->transactionRollback();

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка в процессе обновления заявки на выплату!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }


        /**
         * Update stat status of the withdrawal - status = 1, num_payment, comment
         *
         */
        $updatedStat = $dataMapper->updateStatByIdAndStatus(
            $withdraw['id_stat'], $unprocessedStatus, $transactionHash, $completedStatus,
            $transactionHash
        );
        if($updatedStat === false) {
            $dataMapper->transactionRollback();

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка в процессе обновления статистики автовыплаты!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }

        /**
         * Commit Transaction
         */
        $dataMapper->transactionCommit();
        return [
            'success' => true,
            'data' => [
                'id' => $autoPayoutId,
                'hash' => $transactionHash,
                'email' => $withdraw['email']
            ]
        ];
    }


    private function _generateRandomHash() {
        // Specify the desired length of the random string in bytes
        $lengthInBytes = 32; // 32 bytes will result in a 64-character hexadecimal string

        // Generate random bytes
        $randomBytes = random_bytes($lengthInBytes);

        // Convert the random bytes to a hexadecimal string
        $randomString = bin2hex($randomBytes);

        // Output the random string
        return $randomString;
    }

    private function _updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId) {
        $errorAutoStatus = 2;
        $statusUpdated = $dataMapper->updateAutoPayoutStatusById(
            $autoPayoutId, $errorAutoStatus
        );
        if($statusUpdated === false) {
            return [
                'error' => 'Произошла ошибка при обновлении статуса заявки на автовыплату!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }
        return true;
    }

    public function withdrawMoneyInUSDT_Test(
        int $autoPayoutId, DataMapper $dataMapper, bool $success = true
    ) {
        $unprocessedStatus = 0;
        $completedStatus = 1;
        $paidStatus = 2;

        /**
         * Fetch withdraw wallet and sum
         *
         * [
         *      id =>
         *      email =>
         *      id_stat =>
         *      sum =>
         *      wallet =>
         * ]
         */
        $withdraw = $dataMapper->selectWithdrawByAutoPayoutId($autoPayoutId);
        if($withdraw === false) {
            return [
                'error' => 'Произошла ошибка при получении записи заявки на выплату!',
                'data' => [
                    'id' => $autoPayoutId
                ]
            ];
        }

        /**
         * Process Payment through the Tron Network
         */
        $sum = sprintf("%01.2f", $withdraw['sum']);
//        $transferUSDT = $this->transferUsdt(
//            $fromAddress, $fromPrivateKey, $withdraw['wallet'], $sum
//        );

        /**
         * Simulate the process of sending money
         */
        if($success) {
            $transferUSDT = [
                'result' => true,
                'txid' => $this->_generateRandomHash()
            ];
        } else {
            $transferUSDT = [
                'result' => false
            ];
        }

        if ($transferUSDT['result'] !== true) {
            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }
            return [
                'error' => 'Произошла ошибка при отправке выплаты!',
                'data' => [
                    'id' => $autoPayoutId,
                    'sum' => $withdraw['sum'],
                    'email' => $withdraw['email']
                ]
            ];
        }

        /**
         * Put the value of transaction hash into separate variable
         */
        $transactionHash = $transferUSDT['txid'];


        /**
         * Begin Transaction
         */
        $dataMapper->transactionBegin();

        /**
         * Update auto payout record - completed_time, status = 1, payment_num, comment
         */
        $updatedAutoPayout = $dataMapper->updateAutoPayoutById(
            $autoPayoutId, $transactionHash, $completedStatus, 'cron'
        );
        if($updatedAutoPayout === false) {
            $dataMapper->transactionRollback();

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка в процессе обновления записи автовыплати!',
                'data' => [
                    'id' => $autoPayoutId,
                    'sum' => $withdraw['sum'],
                    'email' => $withdraw['email']
                ]
            ];
        }


        /**
         * Update withdraw status - time_confirm, status = 2
         */
        $updatedWithdraw = $dataMapper->updateWithdrawById(
            $withdraw['id'], $paidStatus
        );
        if($updatedWithdraw === false) {
            $dataMapper->transactionRollback();

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка в процессе обновления заявки на выплату!',
                'data' => [
                    'id' => $autoPayoutId,
                    'sum' => $withdraw['sum'],
                    'email' => $withdraw['email']
                ]
            ];
        }


        /**
         * Update stat status of the withdrawal - status = 1, num_payment, comment
         *
         */
        $updatedStat = $dataMapper->updateStatByIdAndStatus(
            $withdraw['id_stat'], $unprocessedStatus, $transactionHash, $completedStatus,
            $transactionHash
        );
        if($updatedStat === false) {
            $dataMapper->transactionRollback();

            $statusUpdated = $this->_updateAutoPayoutErrorStatus($dataMapper, $autoPayoutId);
            if($statusUpdated !== true) {
                return $statusUpdated;
            }

            return [
                'error' => 'Произошла ошибка в процессе обновления статистики автовыплаты!',
                'data' => [
                    'id' => $autoPayoutId,
                    'sum' => $withdraw['sum'],
                    'email' => $withdraw['email']
                ]
            ];
        }

        /**
         * Commit Transaction
         */
        $dataMapper->transactionCommit();
        return [
            'success' => true,
            'data' => [
                'id' => $autoPayoutId,
                'sum' => $withdraw['sum'],
                'hash' => $transactionHash,
                'email' => $withdraw['email']
            ]
        ];
    }


    function MoneyForProc($id_user=0, $sum = 0, $id_balance=1, $type='plus', $proc=0, $text='', $id_plan=0, $id_investment=0, $num_payment=0){
        $plus = $type == 'plus' ? '+' : ($type == 'minus' ? '-' : json_encode(['error' => 1, 'text' => Lang::getMsg('Operator error', $_SESSION['lang'])], JSON_UNESCAPED_UNICODE));
        $time = time();

        // Инициализация переменных для вставки баланса
        $init_sum = 0;
        $init_status = 1;
        $init_show_balance = 1;

        $check_balance = $this->db->query("SELECT * FROM `users_balances` WHERE `id_balance` = $id_balance AND `id_user` = $id_user");

        if($check_balance->num_rows == 0) {
            $insert_balance = $this->db->prepare("INSERT INTO `users_balances`(`id_user`, `id_balance`, `sum`, `status`, `show_balance`) VALUES (?,?,?,?,?)");
            $insert_balance->bind_param('iiidi', $id_user, $id_balance, $init_sum, $init_status, $init_show_balance);
            $insert_balance->execute();
        }

        $stmt = $this->db->prepare("UPDATE `users_balances` SET `sum` = `sum` $plus ? WHERE `id_balance` = ? AND `id_user` = ?");

        $stmt->bind_param('dii', $sum, $id_balance, $id_user);

        if($stmt->execute()){
            $stmt = $this->db->prepare("INSERT INTO `stat`(`id`, `id_user`, `id_balance`, `time`, `sum`, `proc`, `id_plan`, `id_investment`, `num_payment`, `comment`) VALUES (NULL,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('iiidiiiss', $id_user, $id_balance, $time, $sum, $proc, $id_plan, $id_investment, $num_payment, $text);
            $stmt->execute();
            return $this->db->insert_id;
        }

    }


}
