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
  function displayDealerHand($state, $upcard, $dealerHand, $dealerScore) {
    $html = '';
    if (State::isActive($state)) {
      $html .= getImage($upcard);
    }
    else {
      $html .= getImage($upCard);
      $cards = $dealerHand->getCards();
      foreach($cards as $card) {
        $html .= getImage($card);
      }
      $html .= '<br><br>';
      $html .= 'The dealer has: '.$dealerScore;
    }
    return $html;
  }
?>
<html>
  <head>
    <title>Blackjack by Nick Cohen</title>
    <link href="css/blackjack.css" rel="stylesheet" type="text/css">
    </head>
<body>
  <div class="money">
    <?php 
      $money = $wallet->getMoney();
      if($money >= 0) {
        $class = 'positive';
      }
      else {
        $class = 'negative';
      }
      echo '<span>YOUR CASH: </span><span class="'.$class.'">$'.$money.'</span>';
    ?>

    <form method="post" action="index.php">
      <?php
        $select = 'Wager: ';
        if ($wagerEnabled) { 
          $select .= '<select name="Wager">';
        }
        else {
          $select .= '<select name="Wager" disabled>';
        }
        
        for($i = MIN_WAGER; $i <= MAX_WAGER; $i += WAGER_STEP) {
          if($i == $wallet->getWager()) {
            $select .= '<option selected>';
          }
          else {
            $select .= '<option>';
          }
          $select .= $i;
          $select .= '</option>';
        }
        $select .= '</select>';
        echo $select;
      ?>
      <input type="submit" name="SubmitWager" value="Update"<?php if (!$wagerEnabled) echo 'disabled'; ?>/>
    </form>
  </div>

  <div class="notes">
    <h1>Game Notes</h1>
      <?php 
        if ($gameOn) {
          if (State::isActive($state)) {
            echo "Game is active...";
          }
          else {
            switch ($state) {
              case State::USER_BUST:
                echo "Sorry, you busted!";
                break;
              case State::DEALER_WIN:
                echo "Sorry, the dealer's hand beats yours!";
                break;
              case State::DEALER_BUST:
                echo "Congratulations, you won!  The dealer busted.";
                break;
              case State::USER_WIN:
                echo "Congratulations, you won!  Your hand beats the dealer's.";
                break;
              case State::PUSH:
                echo "Push";
                break;
              case State::USER_BLACKJACK:
                echo "Congratulations, you have Blackjack!";
                break;
              case State::DEALER_BLACKJACK:
                echo "Sorry, the dealer has Blackjack!";
                break;
              default:
                echo "Invalid game state.";
                break;
            }
          }
        }
        else {
          echo "Please press Deal to begin playing.";
        }
      ?>
  </div>
  <div id="dealerhand" class="hand">
    <?php 
      if ($gameOn) {
        echo '<h1>Dealer\'s Hand</h1>';
        echo displayDealerHand($state, $upcard, $dealerHand, $dealerScore);
      }
      else {
        echo '<h1>Press Deal to Play</h1>';
      }
    ?>
  </div>

  <div id="userhand" class="hand">
    <?php 
      if ($gameOn) {
        echo '<h1>Your Hand</h1>';
        $cards = $userHand->getCards();
        foreach($cards as $card) {
          echo getImage($card);
        }
        echo '<br><br>You have: '.$userScore.'<br>';
      }
    ?>
  </div>

  <div class="buttons">
    <form method="post" action="index.php">
      <input type="submit" name="Deal" value="Deal"<?php if (!$dealEnabled) echo 'disabled'; ?>/><input type="submit" name="Hit" value="Hit"<?php if (!$hitEnabled) echo 'disabled'; ?>/><input type="submit" name="Stand" value="Stand"<?php if (!$standEnabled) echo 'disabled'; ?>/><input type="submit" name="Double" value="Double"<?php if (!$doubleEnabled) echo 'disabled'; ?>/><input type="submit" name="BuyInsurance" value="Buy Insurance"<?php if (!$insuranceEnabled) echo 'disabled'; ?>/><input type="submit" name="DeclineInsurance" value="Decline Insurance" <?php if (!$insuranceEnabled) echo 'disabled'; ?>/>
      <br>
      <input type="submit" name="Reset" value="Reset Entire Game" />
    </form>
  </div>
</body>
</html>