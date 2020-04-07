<?php
namespace app\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\EmailMessages;
use app\models\Users;
use app\models\Trips;

/**
 * Helper for Email sending
 */
class EmailHelper
{
	public static function send($event, $trip)
	{
		$message = EmailMessages::find()->where(['active' => 1, 'event' => $event])->limit(1)->one();
		if ($message) {
			$first_amounts = 'No cash requested'; // .Please prepare xxxxx USD +  yyyyyy EUR
			$firstAmounts = $trip->firstAmounts;
			$firstAmountsList = [];
			if (count($firstAmounts) > 0) {
				foreach ($firstAmounts as $firstAmount) {
					$firstAmountsList[] = $firstAmount->currencyCode->cc . ' ' . number_format($firstAmount->first_amount, 2, '.', ',') . ' ' . $firstAmount->currencyCode->currency_sign;
				}
				$first_amounts = 'Please prepare ' . implode(' + ', $firstAmountsList);
			}
			$trip_from = Yii::$app->formatter->asDatetime($trip->departure_date, 'php:d/m/Y \a\t H:i');
			$trip_to = Yii::$app->formatter->asDatetime($trip->arrival_date, 'php:d/m/Y \a\t H:i');
			$flights = $trip->flights;
			$return_from = 'N/A';
			if ($flights) {
				foreach ($flights as $flight) {
					if ($flight->from_country == 123) {
						$trip_from = Yii::$app->formatter->asDatetime($flight->departure_date.' '.$flight->departure_time, 'php:d/m/Y \a\t H:i');
					} elseif ($flight->to_country == 123) {
						$trip_to = Yii::$app->formatter->asDatetime($flight->departure_date.' '.$flight->departure_time, 'php:d/m/Y \a\t H:i');
						if (!empty($flight->fromCountry)) {
							$return_from = $flight->fromCountry->country_en;
						}
					}
				}
			}
			$to_email = [];
			$user = Yii::$app->user->identity;
			$user = Users::findOne($trip->users_id);
			$user_name = $user->full_name;
			$url = Url::toRoute(['trips/view', 'id' => $trip->id], true);
			$manager = Users::findOne(Yii::$app->user->identity->manager_id);
			$treasurers = Users::find()->where(['is_treasurer' => 1])->all();
			switch ($message->to_user) {
				case 0:
					$user = Users::findOne($trip->users_id);
					$to_email[] = Yii::$app->params['treasurer_approve_notify'];
					$user_name = $user->full_name;
					break;
				case 1:
					$user = Users::findOne($trip->users_id);
					$to_email[] = $user->email;
					$user_name = $user->full_name;
					break;
				
				case 2:
					if ($manager) {
						$to_email[] = $manager->email;
					}
					$url = Url::toRoute(['trips-check/view', 'id' => $trip->id], true);
					break;
				
				case 3:
					$url = Url::toRoute(['trips-export/view', 'id' => $trip->id], true);
					foreach ($treasurers as $treasurer) {
						$to_email[] = $treasurer->email;
					}
					break;
			}
			if ($message->to_user == 2 && !$trip->user->need_manager_check) {
				$to_email = [];
			}
			if (is_array($to_email) && count($to_email) > 0) {
				$content = str_replace([
					'{User}',
					'{Trip}',
					'{Url}',
					'{SUM}',
					'{trip_from}',
					'{trip_to}',
					'{user_id}',
					'{Country}',
					'{Plant}',
					'{first_amounts}',
					'{return_from}',
				], [
					$user_name,
					$trip->id,
					'<a href="'.$url.'">'.$url.'</a>',
					abs($trip->total_nis),
					$trip_from,
					$trip_to,
					$user->id,
					$trip->country->country_en,
					Yii::$app->params['plant_ids'][$trip->plant],
					$first_amounts,
					$return_from,
				], $message->text_en.PHP_EOL);

				$subject = str_replace([
					'{User}',
					'{Trip}',
				], [
					$user_name,
					$trip->id,
					$url,
				], $message->subject_en);

				$mail = Yii::$app->mailer->compose([
					'html' => 'main'], [
					'content' => $content,
				]) // a view rendering result becomes the message body here
					->setFrom(Yii::$app->params['notifyEmail'])
					->setTo($to_email)
					->setSubject($subject);
				if ($message->event == 'treasurer.worker.expenses-accept-refunded' || $message->event == 'treasurer.worker.expenses-accept-deducted') {
					$mail->attach(Yii::getAlias('@runtime/EVTravel_Trip_'.$trip->id.'.pdf'));
					Trips::updateAll(['checker_note' => $content], ['id' => $trip->id]);
				}
				$mail->send();
				if ($message->event == 'treasurer.worker.expenses-accept-refunded' || $message->event == 'treasurer.worker.expenses-accept-deducted') {
					@unlink(Yii::getAlias('@runtime/EVTravel_Trip_'.$trip->id.'.pdf'));
				}
			}
		}
	}

	public static function sendApi($data)
	{
		$content = $data;
		$mail = Yii::$app->mailer->compose(['html' => 'main'], ['content' => $content])
			->setFrom(Yii::$app->params['notifyEmail'])
			->setTo(Yii::$app->params['api_notify'])
			->setSubject('Currency API error')
			->send();
	}
}