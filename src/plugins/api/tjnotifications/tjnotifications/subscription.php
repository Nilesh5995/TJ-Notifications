<?php
/**
 * @version    SVN: <svn_id>
 * @package    JTicketing
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models', 'SubscriptionsModel');

/**
 * Class for checkin to tickets for mobile APP
 *
 * @package     Tjnotifications
 * @subpackage  component
 * @since       1.0
 */
class TjnotificationsApiResourceSubscription extends ApiResource
{
	/**
	 * Get method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function get()
	{
		ApiError::raiseError(400, Text::_('GET method not supported'));
	}

	/**
	 * Subscribe method
	 *
	 * @return  json
	 *
	 * @since   1.0
	 */
	public function post()
	{
		$input = Factory::getApplication()->input;

		$title       = $input->post->get('title', '', 'string');
		$backend     = $input->post->get('backend', '', 'string');
		$address     = $input->post->get('address', '', 'string');
		$deviceId    = $input->post->get('device_id', '', 'string');
		$platform    = $input->post->get('platform', '', 'string');
		$state       = $input->post->get('state', '', 'int');
		$isConfirmed = $input->post->get('is_confirmed', '', 'int');

		// GLOBAL - Get TJNotifications subscriptions details for current user
		$model = ListModel::getInstance('Subscriptions', 'TjnotificationsModel', array('ignore_request' => true));
		$model->setState('filter.backend', $backend);
		$model->setState('filter.address', $address);
		$userSubscriptions = $model->getItems();

		if (empty($userSubscriptions))
		{
			$method = 'add';
		}
		else
		{
			$method = 'update';
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/tables');
		$table = Table::getInstance('Subscription', 'TjnotificationsTable');

		$user = Factory::getUser();

		$data = array (
			'user_id'      => $user->id,
			'title'        => $title,
			'backend'      => $backend,
			'address'      => $address,
			'device_id'    => $deviceId,
			'platform'     => $platform,
			'state'        => $state,
			'is_confirmed' => $isConfirmed
		);

		$date = Factory::getDate();

		if ($method == 'update')
		{
			$data['id']          = $userSubscriptions[0]->id;
			$data['modified_by'] = $user->id;
			$data['updated_on']  = $date->toSql(true);
		}
		else
		{
			$data['created_by'] = $user->id;
			$data['created_on'] = $date->toSql(true);
		}

		try
		{
			$table->bind($data);

			// Check and store the object.
			if (!$table->check())
			{
				ApiError::raiseError(400, Text::_($table->getError()));
			}

			// Store the coupon item in the database
			$result = $table->store();

			// Set the id for the coupon object in case we created a new coupon.
			if ($result)
			{
				$table->load($table->id);
			}
		}
		catch (\Exception $e)
		{
			ApiError::raiseError(400, Text::_($e->getMessage()));
		}

		$this->plugin->setResponse($table);
	}
}
