<?php
/*
  This file is part of 'Nick Cohen's Blackjack.'
  
  Copyright © 2009-2010 Nick Cohen
  
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

  defined('SYSPATH') or die('No direct script access.');
?>
<?php
function debug($something) {
  #echo $something."<br>\n";
}

class State {
  // invalid states
  const INVALID = 0;

  // playing states
  const START = 1;
  const OFFER_INSURANCE = 2;
  const PLAYING = 3; // user decision - hit, stand, double, split not supported
  const PLAYING_ACTIVE = 4; // user has already hit (useful for disabling double down)
  const DEALER = 5; // user stands, dealer must decide next action

  // end game states
  const END_GAME_STATES_START = 101;
  const USER_BUST = 101; // user busted
  const DEALER_BUST = 102; // dealer busted
  const PUSH = 103; // tie
  const USER_WIN = 104; // no busting, user score greater than dealer score
  const DEALER_WIN = 105; // no busting, dealer score greater than user score
  const USER_BLACKJACK = 106; // user blackjack
  const DEALER_BLACKJACK = 107; // dealer blackjack
  const RECONCILED = 108;
  const END_GAME_STATES_END = State::RECONCILED;

  public static function isActive($state) {
    return $state > State::INVALID && $state < State::END_GAME_STATES_START;
  }

  public static function isEndGame($state) {
    return $state >= State::END_GAME_STATES_START && $state <= State::END_GAME_STATES_END;
  }

  public static function isUserWin($state) {
    return $state == State::USER_WIN || $state == State::DEALER_BUST || $state == State::USER_BLACKJACK;
  }

  public static function isDealerWin($state) {
    return $state == State::DEALER_WIN || $state == State::USER_BUST || $state == State::DEALER_BLACKJACK;
  }
}

class User {
  private $name;
  private $money;
}

class Hand {
  const WIN = 1;
  const PUSH = 0;
  const LOSE = -1;

  private $cards;
  public function __construct() {
    $this->cards = array();
  }

  public function __toString() {
    $string = "";
    $size = sizeof($this->cards);
    if ($size > 0) {
      $string .= $this->cards[0];
    }
    for ($i = 1; $i < $size; $i++) {
      $string .= ', '.$this->cards[$i];
    }
    return $string;
  }

  public function getScore() {
    $totals = $this->getTotals();
    $score = $totals[0];
    if ($totals[0] != $totals[1]) {
      $score = max($totals);
      if ($score > 21) {
        $score = min($totals);
      }
    }
    return $score;
  }

  public function compareTo($hand) {
    $myscore = $this->getScore();
    $yourscore = $hand->getScore();
    $mybust = $this->isBust();
    $yourbust = $hand->isBust();

    $compareTo = Hand::PUSH;
    if ($mybust && $yourbust) {
      $compareTo = Hand::PUSH;
    }
    elseif ($mybust && !$yourbust) {
      $compareTo = Hand::LOSE;
    }
    elseif (!$mybust && $yourbust) {
      $compareTo = Hand::WIN;
    }
    else {
      if ($myscore > $yourscore) {
        $compareTo = Hand::WIN;
      } elseif ($myscore < $yourscore) {
        $compareTo = Hand::LOSE;
      } else {
        $compareTo = Hand::PUSH;
      }
    }
    return $compareTo;
  }

  public function isBust() {
    $bust = true;
    $totals = $this->getTotals();
    foreach ($totals as $t) {
      if ($t <= 21) {
        $bust = false;
        break;
      }
    }
    return $bust;
  }

  public function isBlackjack() {
    $blackjack = false;
    $totals = $this->getTotals();
    $cardssize = sizeof($this->cards);
    if ($cardssize == 2) {
      foreach ($totals as $t) {
        if ($t == 21) {
          $blackjack = true;
        }
      }
    }
    return $blackjack;
  }

  public function add($card) {
    debug("Hand::add($card) for hand - $this");
    array_push($this->cards, $card);
  }

  public function getCards() {
    return $this->cards;
  }

  /**
  * Returns array of totals (array since hands w/ aces yield two scores)
  */
  public function getTotals() {
    $totals = array(0, 0);
    $cardcount = sizeof($this->cards);
    for ($i = 0; $i < $cardcount; $i++) {
      $card = $this->cards[$i];
      $rank = $card->getRank()->getRank();
      switch ($rank) {
        case Rank::ACE:
        $accountedForAce = false;
        for ($j = 0; $j < $i; $j++) {
          if ($this->cards[$j]->getRank()->getRank() == Rank::ACE) {
            $accountedForAce = true;
            break;
          }
        }
        if ($accountedForAce) {
          // already accounted for soft ace, just add one
          $totals[0] += 1;
          $totals[1] += 1;
        } else {
          // account for soft ace - need to add 1 and 11 to totals
          $totals[0] += 1;
          $totals[1] += 11;
        }
        break;
        case Rank::KING:
        case Rank::QUEEN:
        case Rank::JACK:
        $totals[0] += 10;
        $totals[1] += 10;
        break;
        default:
        $totals[0] += $rank;
        $totals[1] += $rank;
        break;
      }
    }
    return $totals;
  }
}

class DealerHand extends Hand {
  public function getUpCard() {
    $cards = $this->getCards();
    return $cards[1];
  }

  public function getDownCard() {
    $cards = $this->getCards();
    return $cards[0];
  }
}

/**
* User's cash
*/
class Wallet {
  private $money = 1000;
  private $wager;

  public function __construct($money, $wager) {
    $this->money = $money;
    $this->wager = $wager;
  }

  public function setWager($wager) {
    $this->wager = $wager;
  }

  public function getWager() {
    return $this->wager;
  }

  public function addMoney($money) {
    $this->money += $money;
  }

  public function getMoney() {
    return $this->money;
  }

  public function reconcile($game) {
    $state = $game->getState();
    debug("Wallet::reconcile state [$state] money [".$this->money."]");
    $prize = 0;
    $wager = $this->wager;
    if ($state != State::RECONCILED && State::isEndGame($state)) {
      // handle double down
      if ($game->isDoubleDown()) {
        $wager *= 2;
      }

      switch ($state) {
        case State::USER_BLACKJACK:
          $prize = $wager * 1.5;
          break;
        case State::DEALER_BUST:
        case State::USER_WIN:
          $prize = $wager;
          break;
        case State::DEALER_BLACKJACK:
        case State::USER_BUST:
        case State::DEALER_WIN:
          $prize += -$wager;
          break;
      }

      // handle insurance
      if ($game->userInsured()) {
        if ($state == State::DEALER_BLACKJACK) {
          // user original wager insurance
          $prize += $this->wager;
        } else {
          // user original wager insurance
          $prize -= $this->wager / 2;
        }
      }
      $this->money += $prize;
      debug("Wallet::reconcile final prize [$prize] money [".$this->money."]");
    }
    $game->reconcile();
  }
}

class BlackjackGame {
  private $dealer;
  private $user; // could make this an array eventually for multi-player games
  private $state = State::INVALID;
  private $boughtInsurance = false;
  private $doubleDown = false;

  public function __construct($deck = null) {
    if ($deck == null) {
      $deck = new Standard_4x13_InfiniteDeck();
    }
    $this->deck = $deck;
  }

  public function getState() {
    return $this->state;
  }

  private function setState($state) {
    debug("BlackjackGame::setState [$state]");
    $this->state = $state;
  }

  public function start() {
    debug("BlackjackGame start()");
    $this->dealer = new DealerHand();
    $this->user = new Hand();
    $this->boughtInsurance = false;

    // dealer gets dealt first card
    $this->dealer->add($this->deck->next());

    // user(s) get dealt cards next
    $this->user->add($this->deck->next());
    $this->user->add($this->deck->next());

    // dealer gets dealt last card
    $this->dealer->add($this->deck->next());
    $this->setState(State::START);
    $this->updateState();
  }

  public function reconcile() {
    $this->setState(State::RECONCILED);
  }

  public function getUserHand() {
    return $this->user;
  }

  public function getDealerHand() {
    return $this->dealer;
  }

  public function hitUser() {
    $state = $this->state;
    debug("BlackjackGame::hitUser state [$state]");
    if ($state == State::PLAYING_ACTIVE || $state == State::PLAYING) {
      $this->user->add($this->deck->next());
      if ($this->state == State::PLAYING) {
        $this->setState(State::PLAYING_ACTIVE);
      }
      $this->updateState();
      if ($this->doubleDown) {
        if (State::isActive($this->state)) {
          $this->goDealer();
        }
      }
    }
  }

  public function hitDealer() {
    $card = $this->deck->next();
    debug("BlackjackGame::hitDealer card [$card]");
    $this->dealer->add($card);
  }

  public function goDealer() {
    $this->setState(State::DEALER);
    $totals = $this->dealer->getTotals();
    debug("BlackjackGame::goDealer totals [$totals[0], $totals[1]]");
    // assume dealer can stay on soft 17
    $stay = false;
    if ($totals[0] == $totals[1]) {
      if ($totals[0] >= 17) {
        debug("BlackjackGame::goDealer stay 1");
        $stay = true;
      }
    } else {
      if ($totals[0] >= 17) {
        debug("BlackjackGame::goDealer stay 2");
        $stay = true;
      } else {
        if ($totals[1] >= 17 && $totals[1] <= 21) {
          debug("BlackjackGame::goDealer stay 3");
          $stay = true;
        }
      }
    }
    if ($stay) {
      // done
      $this->updateState();
    } else {
      $this->hitDealer();
      $this->goDealer();
    }

  }

  public function userStands() {
    if (State::isActive($this->state)) {
      $this->goDealer();
    }
  }

  public function userDoubleDown() {
    $state = $this->state;
    debug("BlackjackGame::userDoubleDown state [$state]");
    if ($state == State::PLAYING) {
      $this->doubleDown = true;
      $this->hitUser();
    }
  }

  public function isDoubleDown() {
    return $this->doubleDown;
  }

  public function buyInsurance($buy) {
    if ($this->state == State::OFFER_INSURANCE) {
      $this->boughtInsurance = $buy;
      $this->setState(State::PLAYING);
      $this->updateState();
    }
  }

  public function userInsured() {
    return $this->boughtInsurance;
  }

  public function updateState() {
    debug("updateState state[".$this->state."]");
    if ($this->state == State::START) {
      debug("updateState state START");
      if ($this->dealer->getUpCard()->getRank()->getRank() == Rank::ACE) {
        $this->setState(State::OFFER_INSURANCE);
      } else {
        $this->setState(State::PLAYING);
        $this->updateState();
      }
    } else {
      debug("updateState state NOT START");
      if ($this->dealer->isBlackjack()) {
        if ($this->user->isBlackjack()) {
          $this->setState(State::PUSH);
        } else {
          $this->setState(State::DEALER_BLACKJACK);
        }
      } elseif ($this->user->isBlackjack()) {
        $this->setState(State::USER_BLACKJACK);
      } elseif ($this->user->isBust()) {
        $this->setState(State::USER_BUST);
      } elseif ($this->dealer->isBust()) {
        $this->setState(State::DEALER_BUST);
      } elseif ($this->state == State::DEALER) {
        $compare = $this->user->compareTo($this->dealer);
        debug("user hand: ".$this->user.", dealer hand: ".$this->dealer);
        if ($compare == Hand::WIN) {
          $this->setState(State::USER_WIN);
        } elseif ($compare == Hand::LOSE) {
          $this->setState(State::DEALER_WIN);
        } else {
          $this->setState(State::PUSH);
        }
      }
    }
  }
}

class Suit {
  const SPADES = 0;
  const HEARTS = 1;
  const CLUBS = 2;
  const DIAMONDS = 3;

  public static function getRandomSuit() {
    $s = rand(0, 3);
    return new Suit($s);
  }

  private $suit;

  public function __construct($suit) {
    $this->suit = $suit;
  }

  public function __toString() {
    $string = 'NONE';
    switch ($this->suit) {
      case Suit::SPADES:
      $string = 'Spades';
      break;
      case Suit::HEARTS:
      $string = 'Hearts';
      break;
      case Suit::CLUBS:
      $string = 'Clubs';
      break;
      case Suit::DIAMONDS:
      $string = 'Diamonds';
      break;
      default:
      // error, should never get here
      break;
    }
    return $string;
  }

  public function getSuit() {
    return $this->suit;
  }
}

class Rank {

  const ACE = 1;
  const KING = 13;
  const QUEEN = 12;
  const JACK = 11;

  public static function getRandomRank() {
    $s = rand(1, 13);
    return new Rank($s);
  }

  private $rank;
  public function __construct($rank) {
    $this->rank = $rank;
  }

  public function __toString() {
    $string = $this->rank.'';
    switch ($this->rank) {
      case Rank::ACE:
        $string = 'Ace';
        break;
      case Rank::KING:
        $string = 'King';
        break;
      case Rank::QUEEN:
        $string = 'Queen';
        break;
      case Rank::JACK:
        $string = 'Jack';
        break;
    }
    return $string;
  }

  public function getRank() {
    return $this->rank;
  }
}

class Card {
  private $suit;
  private $rank;

  public function __construct($rank, $suit = null) {
    if ($suit == null) {
      $suit = Suit::getRandomSuit();
    }
    $this->suit = $suit;
    $this->rank = $rank;
  }

  public function __toString() {
    return $this->rank.' of '.$this->suit;
  }

  public function getSuit() {
    return $this->suit;
  }

  public function getRank() {
    return $this->rank;
  }
}


/**
* Casinos have different blackjack formats - 6 deck 'shoes' are common, so are continuously shuffled 'shoes'
* the interface is useful to abstract away these details from classes that use Decks like BlackjackGame
*/
interface Deck {
  public function next();
}

class Standard_4x13_InfiniteDeck implements Deck {
  private $cards;
  public function next() {
    $rank = Rank::getRandomRank();
    $card = new Card($rank);
    return $card;
  }
}

?>
