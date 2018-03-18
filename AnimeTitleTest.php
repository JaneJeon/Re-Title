<?php
require 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class AnimeTitleTest extends TestCase {
	public function testFixTitle() {
		$titles = [
			'[bonkai77] Ghost in the Shell Stand Alone Complex - 1x01 - Section 9  [1080p][x265]' =>
			'Ghost in the Shell Stand Alone Complex - 1x01 - Section 9',
			
			'[bonkai77] Your Name (Kimi no Na wa)  [BD-1080p] [DUAL-AUDIO] [x265] [HEVC] [AAC] [10bit]' =>
			'Your Name (Kimi no Na wa)',
			
			'[UTW]_Amagami_SS_-_01_[BD][h264-1080p_FLAC][82910A2B]' =>
			'Amagami SS - 01',
			
			'Black Lagoon - (SUB) - 6 - Moonlit Hunting Grounds' =>
			'Black Lagoon - (SUB) - 6 - Moonlit Hunting Grounds',
			
			'[Coalgirls]_Code_Geass_Picture_Drama_(1920x1080_Blu-ray_FLAC)' =>
			'Code Geass Picture Drama',
			
			'[CBM]_Cowboy_Bebop_-_Session_10_-_Ganymede_Elegy_[720p]_[D6DDA677]' =>
			'Cowboy Bebop - Session 10 - Ganymede Elegy',
			
			'[Coalgirls]_Guilty_Crown_19_(1920x1080_Blu-ray_FLAC)_[7FDE4529]' =>
			'Guilty Crown 19',
			
			'[Coalgirls]_Hyouka_NCOPED_(1920x1080_Blu-Ray_FLAC)_[65DA7CD1]' =>
			'Hyouka NCOPED',
			
			'[Cleo]Kamisama_Hajimemashita_-_01_(Dual Audio_10bit_BD720p)' =>
			'Kamisama Hajimemashita - 01',
			
			'[NoobSubs] Bakemonogatari NCED01 (1080p Blu-ray 8bit AAC)' =>
			'Bakemonogatari NCED01',
			
			'[Erai-raws] Owarimonogatari S2 - 01~02 (Mayoi Hell) [1080p][A92C27DC]' =>
			'Owarimonogatari S2 - 01~02 (Mayoi Hell)'
		];
		
		foreach ($titles as $before => $after)
			$this->assertEquals(fixTitle($before), $after);
	}
	
	public function test_fix_youtube_dl() {
		$files = [
			'Dark Souls 3 - Sunlight Cleric [v5] _ Silver Knight - PVP Guide #113-qXoVFUb8GaA' =>
			'Dark Souls 3 - Sunlight Cleric [v5] _ Silver Knight - PVP Guide #113',
			'Mission 5 - Capital Defense - Ace Combat Joint Assault - HD Walkthrough-oOPUeE8t3Hc' =>
			'Mission 5 - Capital Defense - Ace Combat Joint Assault - HD Walkthrough',
			'dm8m3yz2tof01' => 'dm8m3yz2tof01',
			'AXSHN - Location (N2N & Vasta Remix)-BVBdqkHNl54' =>
			'AXSHN - Location (N2N & Vasta Remix)',
			'xauzwl9ryp4' => 'xauzwl9ryp4',
			'chicken-with-bones-863810' => 'chicken-with-bones-863810',
			'The  Legend of Zelda - Breath of the Wild by atz in 3_57_00 AGDQ 2018-tsUvZ9yiN_U' =>
			'The  Legend of Zelda - Breath of the Wild by atz in 3_57_00 AGDQ 2018',
			'Dark Souls 3 Encounter with a friendly chap-1VA5OP-I_Ls' =>
			'Dark Souls 3 Encounter with a friendly chap'
		];
		
		foreach ($files as $before => $after)
			$this->assertEquals(fix_youtube_dl($before), $after);
	}
}