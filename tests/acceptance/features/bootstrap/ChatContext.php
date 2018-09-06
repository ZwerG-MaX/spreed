<?php

/**
 *
 * @copyright Copyright (c) 2018, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

use Behat\Behat\Context\Context;

class ChatContext implements Context, ActorAwareInterface {

	/**
	 * @var Actor
	 */
	private $actor;

	/**
	 * @var array
	 */
	private $chatAncestorsByActor;

	/**
	 * @var Locator
	 */
	private $chatAncestor;

	/**
	 * @BeforeScenario
	 */
	public function initializeChatAncestors() {
		$this->chatAncestorsByActor = array();
		$this->chatAncestor = null;
	}

	/**
	 * @param Actor $actor
	 */
	public function setCurrentActor(Actor $actor) {
		$this->actor = $actor;

		if (array_key_exists($actor->getName(), $this->chatAncestorsByActor)) {
			$this->chatAncestor = $this->chatAncestorsByActor[$actor->getName()];
		} else {
			$this->chatAncestor = null;
		}
	}

	/**
	 * Sets the chat ancestor to be used in the steps performed by the given
	 * actor from that point on (until changed again).
	 *
	 * This is meant to be called from other contexts, for example, when the
	 * user joins or leaves a video call.
	 *
	 * The ChatAncestorSetter trait can be used to reduce the boilerplate needed
	 * to set the chat ancestor from other contexts.
	 *
	 * @param null|Locator $chatAncestor the chat ancestor
	 * @param Actor $actor the actor
	 */
	public function setChatAncestorForActor($chatAncestor, Actor $actor) {
		$this->chatAncestorsByActor[$actor->getName()] = $chatAncestor;
	}

	/**
	 * @return Locator
	 */
	public static function chatView($chatAncestor) {
		return Locator::forThe()->css(".chat")->
				descendantOf($chatAncestor)->
				describedAs("Chat view in Talk app");
	}

	/**
	 * @return Locator
	 */
	public static function chatMessagesList($chatAncestor) {
		return Locator::forThe()->css(".comments")->
				descendantOf(self::chatView($chatAncestor))->
				describedAs("List of received chat messages");
	}

	/**
	 * @return Locator
	 */
	public static function chatMessage($chatAncestor, $number) {
		return Locator::forThe()->xpath("li[not(contains(concat(' ', normalize-space(@class), ' '), ' systemMessage '))][$number]")->
				descendantOf(self::chatMessagesList($chatAncestor))->
				describedAs("Chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function groupedChatMessage($chatAncestor, $number) {
		return Locator::forThe()->xpath("li[not(contains(concat(' ', normalize-space(@class), ' '), ' systemMessage '))][position() = $number and contains(concat(' ', normalize-space(@class), ' '), ' grouped ')]")->
				descendantOf(self::chatMessagesList($chatAncestor))->
				describedAs("Grouped chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function authorOfChatMessage($chatAncestor, $number) {
		return Locator::forThe()->css(".author")->
				descendantOf(self::chatMessage($chatAncestor, $number))->
				describedAs("Author of chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function textOfChatMessage($chatAncestor, $number) {
		return Locator::forThe()->css(".message")->
				descendantOf(self::chatMessage($chatAncestor, $number))->
				describedAs("Text of chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function textOfGroupedChatMessage($chatAncestor, $number) {
		return Locator::forThe()->css(".message")->
				descendantOf(self::groupedChatMessage($chatAncestor, $number))->
				describedAs("Text of grouped chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function formattedMentionInChatMessageOf($chatAncestor, $number, $user) {
		return Locator::forThe()->xpath("span/span[contains(concat(' ', normalize-space(@class), ' '), ' mention-user ') and normalize-space() = '$user']")->
				descendantOf(self::textOfChatMessage($chatAncestor, $number))->
				describedAs("Formatted mention of $user in chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function formattedLinkInChatMessageTo($chatAncestor, $number, $url) {
		return Locator::forThe()->xpath("a[normalize-space(@href) = '$url']")->
				descendantOf(self::textOfChatMessage($chatAncestor, $number))->
				describedAs("Formatted link to $url in chat message $number in the list of received messages");
	}

	/**
	 * @return Locator
	 */
	public static function newChatMessageForm($chatAncestor) {
		return Locator::forThe()->css(".newCommentForm")->
				descendantOf(self::chatView($chatAncestor))->
				describedAs("New chat message form");
	}

	/**
	 * @return Locator
	 */
	public static function newChatMessageInput($chatAncestor) {
		return Locator::forThe()->css(".message")->
				descendantOf(self::newChatMessageForm($chatAncestor))->
				describedAs("New chat message input");
	}

	/**
	 * @return Locator
	 */
	public static function newChatMessageWorkingIcon($chatAncestor) {
		return Locator::forThe()->css(".submitLoading")->
				descendantOf(self::newChatMessageForm($chatAncestor))->
				describedAs("New chat message working icon");
	}

	/**
	 * @When I send a new chat message with the text :message
	 */
	public function iSendANewChatMessageWith($message) {
		// Instead of waiting for the input to be enabled before sending a new
		// message it is easier to wait for the working icon to not be shown.
		if (!WaitFor::elementToBeEventuallyNotShown(
				$this->actor,
				self::newChatMessageWorkingIcon($this->chatAncestor),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			PHPUnit_Framework_Assert::fail("The working icon for the new message was still being shown after $timeout seconds");
		}

		$this->actor->find(self::newChatMessageInput($this->chatAncestor), 10)->setValue($message . "\r");
	}

	/**
	 * @Then I see that the chat is shown in the main view
	 */
	public function iSeeThatTheChatIsShownInTheMainView() {
		PHPUnit_Framework_Assert::assertTrue($this->actor->find(self::chatView($this->chatAncestor), 10)->isVisible());
	}

	/**
	 * @Then I see that the message :number was sent by :author with the text :message
	 */
	public function iSeeThatTheMessageWasSentByWithTheText($number, $author, $message) {
		if (!WaitFor::elementToBeEventuallyShown(
				$this->actor,
				self::authorOfChatMessage($this->chatAncestor, $number),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			PHPUnit_Framework_Assert::fail("The author of the message $number was not shown yet after $timeout seconds");
		}
		PHPUnit_Framework_Assert::assertEquals($author, $this->actor->find(self::authorOfChatMessage($this->chatAncestor, $number))->getText());

		if (!WaitFor::elementToBeEventuallyShown(
				$this->actor,
				self::textOfChatMessage($this->chatAncestor, $number),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			PHPUnit_Framework_Assert::fail("The text of the message $number was not shown yet after $timeout seconds");
		}
		PHPUnit_Framework_Assert::assertEquals($message, $this->actor->find(self::textOfChatMessage($this->chatAncestor, $number))->getText());
	}

	/**
	 * @Then I see that the message :number was sent with the text :message and grouped with the previous one
	 */
	public function iSeeThatTheMessageWasSentWithTheTextAndGroupedWithThePreviousOne($number, $message) {
		if (!WaitFor::elementToBeEventuallyShown(
				$this->actor,
				self::textOfGroupedChatMessage($this->chatAncestor, $number),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			PHPUnit_Framework_Assert::fail("The text of the message $number was not shown yet after $timeout seconds");
		}
		PHPUnit_Framework_Assert::assertEquals($message, $this->actor->find(self::textOfGroupedChatMessage($this->chatAncestor, $number))->getText());

		// Author element is not visible for the message, so its text is
		// returned as an empty string (even if the element has actual text).
		PHPUnit_Framework_Assert::assertEquals("", $this->actor->find(self::authorOfChatMessage($this->chatAncestor, $number))->getText());
	}

	/**
	 * @Then I see that the message :number contains a formatted mention of :user
	 */
	public function iSeeThatTheMessageContainsAFormattedMentionOf($number, $user) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::formattedMentionInChatMessageOf($this->chatAncestor, $number, $user), 10));
	}

	/**
	 * @Then I see that the message :number contains a formatted link to :user
	 */
	public function iSeeThatTheMessageContainsAFormattedLinkTo($number, $url) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::formattedLinkInChatMessageTo($this->chatAncestor, $number, $url), 10));
	}

}
