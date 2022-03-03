<?php 
    namespace App;
    use LaravelFCM\Message\OptionsBuilder;
    use LaravelFCM\Message\PayloadDataBuilder;
    use LaravelFCM\Message\PayloadNotificationBuilder;
    use FCM;
    use App\Models\FcmToken;
    
    // $downstreamResponse->numberSuccess();
    // $downstreamResponse->numberFailure();
    // $downstreamResponse->numberModification();

    // // return Array - you must remove all this tokens in your database
    // $downstreamResponse->tokensToDelete();

    // // return Array (key : oldToken, value : new token - you must change the token in your database)
    // $downstreamResponse->tokensToModify();

    // // return Array - you should try to resend the message to the tokens in the array
    // $downstreamResponse->tokensToRetry();

    // // return Array (key:token, value:error) - in production you should remove from your database the tokens
    // $downstreamResponse->tokensWithError();


    class FCMHelper{
        
        public static function sendDownstreamMessageToDevice($token, $title, $message){
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60*20);

            $notificationBuilder = new PayloadNotificationBuilder($title);
            $notificationBuilder->setBody($message)
                                ->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData(['a_data' => 'my_data']);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

            // $token = "a_registration_from_your_database";

            $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);

            return $downstreamResponse;

        }


        public static function sendDownstreamMessageToDevices($tokens, $title, $message){
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60*20);

            $notificationBuilder = new PayloadNotificationBuilder($title);
            $notificationBuilder->setBody($message)
                                ->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData(['a_data' => 'my_data']);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

            // $token = "a_registration_from_your_database";

            $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

            return $downstreamResponse;
        }

        public static function getFCMTokens(){
            $fcmTokens = [];
            $tokens = FcmToken::all();
            foreach ($tokens as $token) {
                $fcmTokens[] = $token->fcm_token;
            }
            return $fcmTokens;
        }

        public static function sendPushNotificationQuizPub(){

            $tokens = FCMHelper::getFCMTokens();
            if(count($tokens) == 0){
                throw new Exception("No FCM Tokens found!!!");
            }
            try {
               return FCMHelper::sendDownstreamMessageToDevices($tokens, '[Quiz App]','New Quiz is Published');
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
                
            }
        }

        public static function sendPushNotificationQuizResPub(){

            $tokens = FCMHelper::getFCMTokens();
            if(count($tokens) == 0){
                throw new Exception("No FCM Tokens found!!!");
            }
            try {
               return FCMHelper::sendDownstreamMessageToDevices($tokens, '[Quiz App]','Quiz Results are out');
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
                
            }
        }

    }

?>