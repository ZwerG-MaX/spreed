<?php
declare(strict_types=1);
/**
 *
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Spreed\Chat;

use OCP\Comments\IComment;
use OCP\IL10N;
use OCP\IUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Helper class to get a rich message from a plain text message.
 */
class MessageParser {

	/** @var EventDispatcherInterface */
	private $dispatcher;

	public function __construct(EventDispatcherInterface $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	public function parseMessage(IComment $chatMessage, IL10N $l, IUser $user = null): array {
		$event = new GenericEvent($chatMessage, [
			'user' => $user,
			'l10n' => $l,
		]);
		$this->dispatcher->dispatch(self::class . '::parseMessage', $event);

		if ($event->hasArgument('message')) {
			return [$event->getArgument('message'), $event->getArgument('parameters')];
		}

		return [$chatMessage->getMessage(), []];
	}
}
