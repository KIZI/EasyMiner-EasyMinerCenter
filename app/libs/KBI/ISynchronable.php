<?php
/**
 * @version		$Id: ISynchronable.php 842 2013-04-09 01:06:20Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

///require_once 'IHasDataDictionary.php';

/**
 * Interface for document management between CMSs and KBs
 *
 * @todo ve výsledcích vyhedávání je potřeba  provázat nalezené pravidlo hypertextovým odkazem s konkrétním pravidlem v konkrétním reportu. Např. hyperlink "http://sewebar-dev2.vse.cz/home/1-xslt#sect5-rule2" -> Xquery je tedy při indexaci potřeba předat i Joomla id indexovaného dokumentu. -> bude nutné rozšířit výsledky vyhledávání o joomla id (zatím je tam jen XQuery ID)
 * @todo Při synchronizaci by se měli porovnávat dokumenty, tak aby se zbytečně nesynchronizvaly stejné dokumenty, nebo se omylem nepřepsal novější dokument.
 *
 * @package KBI
 */
interface ISynchronable extends IHasDataDictionary
{
	public function getDocuments();

	/**
	 * Metoda pro zobrazeni dokumentu z XML DB
	 * @param id id dokumentu
	 * @param mgr XmlManager
	 * @return Zobrazeni dokumentu/chyba
	 */
	public function getDocument($id);

	/**
	 * Metoda pro vloĂ„ĹąÄąÄ˝Ă‹ĹĄenĂ„ĹąÄąÄ˝Ă‹ĹĄ dokumentu do XML databĂ„ĹąÄąÄ˝Ă‹ĹĄze
	 *
	 * @param document obsah novĂ„ĹąÄąÄ˝Ă‹ĹĄho dokumentu
	 * @param id nĂ„ĹąÄąÄ˝Ă‹ĹĄzev novĂ„ĹąÄąÄ˝Ă‹ĹĄho dokumentu
	 * @return String output - uloĂ„ĹąÄąÄ˝Ă‹ĹĄeno/neuloĂ„ĹąÄąÄ˝Ă‹ĹĄeno
	 */
	public function addDocument($id, $document, $path = true);

	//public function moreDocuments(String $docs, String $names);

	/**
	 * Metoda pro vymazani z XML db
	 * @param id id dokumentu
	 * @param mgr XmlManager
	 * @return Zprava - splneno/chyba
	 */
	public function deleteDocument($id);

	/**
	 * Metoda pro uloĂ„ĹąÄąÄ˝Ă‹ĹĄenĂ„ĹąÄąÄ˝Ă‹ĹĄ query
	 * @param query obsah query
	 * @param id identifikace query
	 * @return String output - uloĂ„ĹąÄąÄ˝Ă‹ĹĄena/neuloĂ„ĹąÄąÄ˝Ă‹ĹĄena - jiĂ„ĹąÄąÄ˝Ă‹ĹĄ existuje
	 */
	//public function addQuery(String $query, String $id);

	/**
	 * Metoda slouĂ„ĹąÄąÄ˝Ă‹ĹĄĂ„ĹąÄąÄ˝Ă‹ĹĄcĂ„ĹąÄąÄ˝Ă‹ĹĄ k vymazĂ„ĹąÄąÄ˝Ă‹ĹĄnĂ„ĹąÄąÄ˝Ă‹ĹĄ uloĂ„ĹąÄąÄ˝Ă‹ĹĄenĂ„ĹąÄąÄ˝Ă‹ĹĄ query
	 * @param id identifikace query
	 * @return String output - vymazĂ„ĹąÄąÄ˝Ă‹ĹĄna/nenalezena
	 */
	//public function deleteQuery(String $id);

	/**
	 * Metoda pro zĂ„ĹąÄąÄ˝Ă‹ĹĄskĂ„ĹąÄąÄ˝Ă‹ĹĄnĂ„ĹąÄąÄ˝Ă‹ĹĄ uloĂ„ĹąÄąÄ˝Ă‹ĹĄenĂ„ĹąÄąÄ˝Ă‹ĹĄ query
	 * @param id identifikace query
	 * @return String output - obsah query/nenalezena
	 */
	//public function getQuery(String $id);

	/**
	 * Metoda slouĂ„ĹąÄąÄ˝Ă‹ĹĄĂ„ĹąÄąÄ˝Ă‹ĹĄ pro vyhledĂ„ĹąÄąÄ˝Ă‹ĹĄvĂ„ĹąÄąÄ˝Ă‹ĹĄnĂ„ĹąÄąÄ˝Ă‹ĹĄ v XML databĂ„ĹąÄąÄ˝Ă‹ĹĄzi podle zadanĂ„ĹąÄąÄ˝Ă‹ĹĄ podmĂ„ĹąÄąÄ˝Ă‹ĹĄnky
	 * @param id identifikace query, podle kterĂ„ĹąÄąÄ˝Ă‹ĹĄ vyhledĂ„ĹąÄąÄ˝Ă‹ĹĄvat
	 * @param search vstupnĂ„ĹąÄąÄ˝Ă‹ĹĄ podmĂ„ĹąÄąÄ˝Ă‹ĹĄnka pro query
	 */
	//public function query(String $id, String $search, int $typ);
}