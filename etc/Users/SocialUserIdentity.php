<?php
/**
 * @link      https://dukt.net/craft/social/
 * @copyright Copyright (c) 2015, Dukt
 * @license   https://dukt.net/craft/social/docs/license
 */

namespace Dukt\Social\Etc\Users;

use Craft\UserModel;
use Craft\UserStatus;

/**
 * SocialUserIdentity represents the data needed to identify a user with a token and an email
 * It contains the authentication method that checks if the provided data can identity the user.
 */

class SocialUserIdentity extends \Craft\UserIdentity
{
    // Properties
    // =========================================================================

    public $accountId;

    /**
     * @var int
     */
    private $_id;

    /**
     * @var UserModel
     */
    private $_userModel;

    /**
     * Constructor
     *
     * @param int $accountId
     *
     * @return null
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;


        $account = \Craft\craft()->social_loginAccounts->getLoginAccountById($this->accountId);

        if($account)
        {
            $this->_userModel = $account->getUser();
        }
    }

    /**
     * @return UserModel
     */
    public function getUserModel()
    {
        return $this->_userModel;
    }

	/**
     * Authenticate
     *
     * @return bool
     */
    public function authenticate()
    {
        $account = \Craft\craft()->social_loginAccounts->getLoginAccountById($this->accountId);

        if($account)
        {
            $user = $account->getUser();

            if($user)
            {
                return $this->_processUserStatus($user);
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    public function _processUserStatus(UserModel $user)
    {
        switch ($user->status)
        {
            // If the account is pending, they don't exist yet.
            case UserStatus::Archived:
            {
                $this->errorCode = static::ERROR_USERNAME_INVALID;
                break;
            }

            case UserStatus::Locked:
            {
                $this->errorCode = $this->_getLockedAccountErrorCode();
                break;
            }

            case UserStatus::Suspended:
            {
                $this->errorCode = static::ERROR_ACCOUNT_SUSPENDED;
                break;
            }

            case UserStatus::Pending:
            {
                $this->errorCode = static::ERROR_PENDING_VERIFICATION;
                break;
            }

            case UserStatus::Active:
            {
                $this->_id = $user->id;
                $this->username = $user->username;

                // Everything is good.
                $this->errorCode = static::ERROR_NONE;

                break;
            }

            default:
            {
                throw new Exception(Craft::t('User has unknown status “{status}”', array($user->status)));
            }
        }

        return $this->errorCode === static::ERROR_NONE;
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }
}