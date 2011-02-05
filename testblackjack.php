<?php
/*
    This file is part of 'Nick Cohen's Blackjack.'
    
    Copyright © 2009 Nick Cohen

    'Nick Cohen's Blackjack' is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    'Nick Cohen's Blackjack' is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with 'Nick Cohen's Blackjack'.  If not, see <http://www.gnu.org/licenses/>.
 */
 
  // system defines
  define('SYSPATH', '.');
?>
<?php 
require_once ('simpletest/reporter.php');
class ShowPasses extends HtmlReporter {

    function paintPass($message) {
        parent::paintPass($message);
        print "&<span class=\"pass\">Pass</span>: ";
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        print implode("->", $breadcrumb);
        print "->$message<br />\n";
    }
    
    protected function getCss() {
        return parent::getCss().' .pass { color: green; }';
    }
}
SimpleTest::prefer( new ShowPasses());
require_once ('simpletest/autorun.php');
require_once ('blackjack.php');


class TestDeck implements Deck {
    private $deck = 0;
    
    public function __construct($cardarray) {
        $this->deck = $cardarray;
    }
    
    public function next() {
        return array_shift($this->deck);
    }
}

class TestBlackjackGame extends UnitTestCase {
    public function testOfferInsurance() {
        $cards = array( new Card( new Rank(10)), new Card( new Rank(8)), new Card( new Rank(9)), new Card( new Rank(Rank::ACE)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
    }
    
    public function testUserBust() {
        $cards = array( new Card( new Rank(10)), new Card( new Rank(6)), new Card( new Rank(9)), new Card( new Rank(10)), new Card( new Rank(10)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $game->hitUser();
        $this->assertTrue($game->getState() == State::USER_BUST);
    }
    
    public function testDealerBust() {
        $cards = array( new Card( new Rank(10)), new Card( new Rank(6)), new Card( new Rank(9)), new Card( new Rank(6)), new Card( new Rank(10)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $game->userStands();
        $this->assertTrue($game->getState() == State::DEALER_BUST);
    }
    
    public function testPush() {
        $cards = array( new Card( new Rank(10)), new Card( new Rank(5)), new Card( new Rank(9)), new Card( new Rank(6)), new Card( new Rank(3)), new Card( new Rank(Rank::ACE)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $game->hitUser();
        $game->userStands();
        $this->assertTrue($game->getState() == State::PUSH);
    }
    
    public function testDealerBlackjack() {
        $cards = array( new Card( new Rank(Rank::ACE)), new Card( new Rank(8)), new Card( new Rank(9)), new Card( new Rank(10)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::DEALER_BLACKJACK);
        
        $cards = array( new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::JACK)), new Card( new Rank(10)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::PUSH);
    }
    
    public function testUserBlackjack() {
        $cards = array( new Card( new Rank(4)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::QUEEN)), new Card( new Rank(10)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::USER_BLACKJACK);
    }
    
    public function testDoubleDown() {
        // double down win
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(8)), new Card( new Rank(8)), new Card( new Rank(3)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::KING)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        $this->assertTrue($game->getDealerHand()->getScore() == 19);
        $this->assertTrue($game->getUserHand()->getScore() == 21);
        $this->assertTrue($game->getState() == State::USER_WIN);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1400);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1400);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1400);
        
        // double down win, dealer bust
        $wallet = new Wallet(1000, 400);
        $cards = array( new Card( new Rank(2)), new Card( new Rank(8)), new Card( new Rank(3)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::ACE)), new Card( new Rank(3)), new Card( new Rank(Rank::KING)), new Card( new Rank(Rank::KING)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        $this->assertTrue($game->getDealerHand()->getScore() == 26);
        $this->assertTrue($game->getUserHand()->getScore() == 12);
        $this->assertTrue($game->getState() == State::DEALER_BUST);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1800);
        $wallet->reconcile($game);
        
        // double down loss no bust
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(3)), new Card( new Rank(5)), new Card( new Rank(5)), new Card( new Rank(Rank::ACE)), new Card( new Rank(5)), new Card( new Rank(2)), new Card( new Rank(4)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        // try to hit a few times, should do nothing
        $game->hitUser();
        $game->hitUser();
        $this->assertTrue($game->getDealerHand()->getScore() == 20);
        $this->assertTrue($game->getUserHand()->getScore() == 15);
        $this->assertTrue($game->getState() == State::DEALER_WIN);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 600);
        
        // double down loss via bust
        $wallet = new Wallet(1000, 500);
        $cards = array( new Card( new Rank(2)), new Card( new Rank(7)), new Card( new Rank(Rank::QUEEN)), new Card( new Rank(Rank::JACK)), new Card( new Rank(8)), new Card( new Rank(10)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        // try to hit a few times, should do nothing
        $game->hitUser();
        $game->hitUser();
        $this->assertTrue($game->getDealerHand()->getScore() == 12);
        $this->assertTrue($game->getUserHand()->getScore() == 25);
        $this->assertTrue($game->getState() == State::USER_BUST);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 0);
        
        // double down w/ insurance & win
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(8)), new Card( new Rank(8)), new Card( new Rank(3)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::KING)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(true);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        $this->assertTrue($game->getDealerHand()->getScore() == 19);
        $this->assertTrue($game->getUserHand()->getScore() == 21);
        $this->assertTrue($game->getState() == State::USER_WIN);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1300);
		
		// double down w/o insurance & win
        $wallet = new Wallet(1000, 50);
        $cards = array( new Card( new Rank(4)), new Card( new Rank(6)), new Card( new Rank(5)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::QUEEN)), new Card( new Rank(3)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        $this->assertTrue($game->getDealerHand()->getScore() == 18);
        $this->assertTrue($game->getUserHand()->getScore() == 12);
        $this->assertTrue($game->getState() == State::DEALER_WIN);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 900);
        
        // double down w/ insurance & loss
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(8)), new Card( new Rank(9)), new Card( new Rank(3)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::KING)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(true);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userDoubleDown();
        $this->assertTrue($game->getDealerHand()->getScore() == 19);
        $this->assertTrue($game->getUserHand()->getScore() == 22);
        $this->assertTrue($game->getState() == State::USER_BUST);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 500);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 500);
        
    }
    
    public function testInsurance() {
        // buy insurance w/ dealer blackjack
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(10)), new Card( new Rank(8)), new Card( new Rank(9)), new Card( new Rank(Rank::ACE)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(true);
        $this->assertTrue($game->getState() == State::DEALER_BLACKJACK);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
        
        // buy insurance w/o dealer blackjack
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(5)), new Card( new Rank(8)), new Card( new Rank(9)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::QUEEN)), new Card( new Rank(Rank::QUEEN)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(true);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->userStands();
        $this->assertTrue($game->getState() == State::DEALER_BUST);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1100);
        
        // no insurance
        $wallet = new Wallet(1000, 200);
        $cards = array( new Card( new Rank(Rank::QUEEN)), new Card( new Rank(3)), new Card( new Rank(5)), new Card( new Rank(Rank::ACE)), new Card( new Rank(4)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->hitUser();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::DEALER_BLACKJACK);
        $this->assertTrue($game->getDealerHand()->getScore() == 21);
        $this->assertTrue($game->getUserHand()->getScore() == 8);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 800);
        
        // push w/ insurance
        $wallet = new Wallet(1000, 50);
        $cards = array( new Card( new Rank(10)), new Card( new Rank(10)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::ACE)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(true);
        $this->assertTrue($game->getState() == State::PUSH);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 975);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 975);
        
        // push w/o insurance
        $wallet = new Wallet(1000, 50);
        $cards = array( new Card( new Rank(10)), new Card( new Rank(10)), new Card( new Rank(Rank::ACE)), new Card( new Rank(Rank::ACE)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::PUSH);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
    }
    
    public function testRegularGame() {
        $wallet = new Wallet(1000, 100);
        $cards = array( new Card( new Rank(4)), new Card( new Rank(8)), new Card( new Rank(3)), new Card( new Rank(Rank::QUEEN)), new Card( new Rank(5)), new Card( new Rank(Rank::ACE)), new Card( new Rank(3)));
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->hitUser();
        $this->assertTrue($game->getState() == State::PLAYING_ACTIVE);
        $game->userStands();
        $this->assertTrue($game->getState() == State::DEALER_WIN);
        $this->assertTrue($game->getDealerHand()->getScore() == 18);
        $this->assertTrue($game->getUserHand()->getScore() == 16);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 900);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 900);
        
        $cards = array();
        for ($i = 0; $i < 100; $i++) {
            array_push($cards, new Card( new Rank(Rank::ACE)));
        }
        $game = new BlackjackGame( new TestDeck($cards));
        $game->start();
        $this->assertTrue($game->getState() == State::OFFER_INSURANCE);
        $game->buyInsurance(false);
        $this->assertTrue($game->getState() == State::PLAYING);
        $game->hitUser(); // 3
        $this->assertTrue($game->getState() == State::PLAYING_ACTIVE);
        $game->hitUser(); // 4
        $game->hitUser(); // 5
        $game->hitUser(); // 6
        $game->hitUser(); // 7
        $game->hitUser(); // 8
        $game->hitUser(); // 9
        $game->hitUser(); // 10
        $game->hitUser(); // 11/21
        $game->userStands();
        $this->assertTrue($game->getState() == State::USER_WIN);
        $this->assertTrue($game->getDealerHand()->getScore() == 17);
        $this->assertTrue($game->getUserHand()->getScore() == 21);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
        $wallet->reconcile($game);
        $this->assertTrue($wallet->getMoney() == 1000);
    }
}

class TestOfHand extends UnitTestCase {

    public function testIsBlackjack() {
        $hand = new Hand();
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::KING)));
        $this->assertTrue($hand->isBlackjack());
        
        $hand = new Hand();
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::KING)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $this->assertFalse($hand->isBlackjack());
        
        $hand = new Hand();
        $hand->add( new Card( new Rank(5)));
        $hand->add( new Card( new Rank(Rank::JACK)));
        $this->assertFalse($hand->isBlackjack());
    }
    
    public function testGetTotals() {
        $hand = new Hand();
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::KING)));
        $totals = $hand->getTotals();
        $this->assertTrue($totals[0] == 11);
        $this->assertTrue($totals[1] == 21);
        
        $hand = new Hand();
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $totals = $hand->getTotals();
        $this->assertTrue($totals[0] == 7);
        $this->assertTrue($totals[1] == 17);
        
        $hand = new Hand();
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::KING)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $hand->add( new Card( new Rank(Rank::ACE)));
        $totals = $hand->getTotals();
        $this->assertTrue($totals[0] == 17);
        $this->assertTrue($totals[1] == 27);
        
        $hand = new Hand();
        $hand->add( new Card( new Rank(7)));
        $hand->add( new Card( new Rank(9)));
        $totals = $hand->getTotals();
        $this->assertTrue($totals[0] == 16);
        $this->assertTrue($totals[1] == 16);
    }
    
    public function testCompareTo() {
        // standard win/loss
        $hand1 = new Hand();
        $hand1->add( new Card( new Rank(5)));
        $hand1->add( new Card( new Rank(7)));
        $hand2 = new Hand();
        $hand2->add( new Card( new Rank(9)));
        $hand2->add( new Card( new Rank(9)));
        $this->assertTrue($hand1->compareTo($hand2) == Hand::LOSE);
        $this->assertTrue($hand2->compareTo($hand1) == Hand::WIN);
        
        // with soft ace
        $hand1 = new Hand();
        $hand1->add( new Card( new Rank(RANK::ACE)));
        $hand1->add( new Card( new Rank(8)));
        $hand2 = new Hand();
        $hand2->add( new Card( new Rank(9)));
        $hand2->add( new Card( new Rank(9)));
        $this->assertTrue($hand1->compareTo($hand2) == Hand::WIN);
        $this->assertTrue($hand2->compareTo($hand1) == Hand::LOSE);
        
        // another with soft ace
        $hand1 = new Hand();
        $hand1->add( new Card( new Rank(4)));
        $hand1->add( new Card( new Rank(RANK::QUEEN)));
        $hand1->add( new Card( new Rank(RANK::ACE)));
        $hand1->add( new Card( new Rank(3)));
        $hand2 = new Hand();
        $hand2->add( new Card( new Rank(8)));
        $hand2->add( new Card( new Rank(3)));
        $hand2->add( new Card( new Rank(5)));
        $this->assertTrue($hand1->compareTo($hand2) == Hand::WIN);
        $this->assertTrue($hand2->compareTo($hand1) == Hand::LOSE);
        
        // push with soft ace
        $hand1 = new Hand();
        $hand1->add( new Card( new Rank(4)));
        $hand1->add( new Card( new Rank(RANK::ACE)));
        $hand1->add( new Card( new Rank(3)));
        $hand2 = new Hand();
        $hand2->add( new Card( new Rank(8)));
        $hand2->add( new Card( new Rank(3)));
        $hand2->add( new Card( new Rank(7)));
        $this->assertTrue($hand1->compareTo($hand2) == Hand::PUSH);
        $this->assertTrue($hand2->compareTo($hand1) == Hand::PUSH);
        
        // bust
        $hand1 = new Hand();
        $hand1->add( new Card( new Rank(RANK::KING)));
        $hand1->add( new Card( new Rank(8)));
        $hand1->add( new Card( new Rank(RANK::KING)));
        $hand2 = new Hand();
        $hand2->add( new Card( new Rank(9)));
        $hand2->add( new Card( new Rank(9)));
        $this->assertTrue($hand1->compareTo($hand2) == Hand::LOSE);
        $this->assertTrue($hand2->compareTo($hand1) == Hand::WIN);
    }
}

class TestState extends UnitTestCase {
    public function testIsEndGame() {
        $this->assertTrue(State::isEndGame(State::USER_BUST));
        $this->assertTrue(State::isEndGame(State::DEALER_BUST));
        $this->assertTrue(State::isEndGame(State::PUSH));
        $this->assertTrue(State::isEndGame(State::USER_WIN));
        $this->assertTrue(State::isEndGame(State::DEALER_WIN));
        $this->assertTrue(State::isEndGame(State::USER_BLACKJACK));
        $this->assertTrue(State::isEndGame(State::DEALER_BLACKJACK));
        $this->assertFalse(State::isEndGame(State::INVALID));
        $this->assertFalse(State::isEndGame(State::START));
        $this->assertFalse(State::isEndGame(State::PLAYING));
        $this->assertFalse(State::isEndGame(State::PLAYING_ACTIVE));
        $this->assertFalse(State::isEndGame(State::OFFER_INSURANCE));
    }
}

class TestWallet extends UnitTestCase {
    public function testWin() {
    
    }
}
?>
