<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

/**
 * Interface for indexable article-document.
 */
interface ArticleViewDocumentInterface
{
    /**
     * Returns id.
     *
     * @return string
     */
    public function getId();

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * Returns uuid.
     *
     * @return string
     */
    public function getUuid();

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return $this
     */
    public function setUuid($uuid);

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale);

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * Returns route-path.
     *
     * @return string
     */
    public function getRoutePath();

    /**
     * Set route-path.
     *
     * @param string $routePath
     *
     * @return $this
     */
    public function setRoutePath($routePath);

    /**
     * Returns type.
     *
     * @return string
     */
    public function getType();

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type);

    /**
     * Returns type translation.
     *
     * @return string
     */
    public function getTypeTranslation();

    /**
     * Set type translation.
     *
     * @param string $typeTranslation
     *
     * @return $this
     */
    public function setTypeTranslation($typeTranslation);

    /**
     * Returns structure-type.
     *
     * @return string
     */
    public function getStructureType();

    /**
     * Set structure-type.
     *
     * @param string $structureType
     *
     * @return $this
     */
    public function setStructureType($structureType);

    /**
     * Returns changer full name.
     *
     * @return string
     */
    public function getChangerFullName();

    /**
     * Set changer full name.
     *
     * @param string $changerFullName
     *
     * @return $this
     */
    public function setChangerFullName($changerFullName);

    /**
     * Returns creator full name.
     *
     * @return string
     */
    public function getCreatorFullName();

    /**
     * Set creator full name.
     *
     * @param string $creatorFullName
     *
     * @return $this
     */
    public function setCreatorFullName($creatorFullName);

    /**
     * Return changed.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed);

    /**
     * Returns created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return $this
     */
    public function setCreated($created);

    /**
     * Returns excerpt.
     *
     * @return ExcerptViewObject
     */
    public function getExcerpt();

    /**
     * Set excerpt.
     *
     * @param ExcerptViewObject $excerpt
     *
     * @return $this
     */
    public function setExcerpt(ExcerptViewObject $excerpt);

    /**
     * Returns seo.
     *
     * @return SeoViewObject
     */
    public function getSeo();

    /**
     * Set seo.
     *
     * @param SeoViewObject $seo
     *
     * @return $this
     */
    public function setSeo(SeoViewObject $seo);

    /**
     * Returns authored.
     *
     * @return \DateTime
     */
    public function getAuthored();

    /**
     * Set authored date.
     *
     * @param \DateTime $authored
     *
     * @return $this
     */
    public function setAuthored(\DateTime $authored = null);

    /**
     * Returns author full name.
     *
     * @return string
     */
    public function getAuthorFullName();

    /**
     * Set author full name.
     *
     * @param string $authorFullName
     *
     * @return $this
     */
    public function setAuthorFullName($authorFullName);

    /**
     * Returns teaser-description.
     *
     * @return string
     */
    public function getTeaserDescription();

    /**
     * Set teaser-description.
     *
     * @param string $teaserDescription
     *
     * @return $this
     */
    public function setTeaserDescription($teaserDescription);

    /**
     * Returns teaser-media-id.
     *
     * @return int
     */
    public function getTeaserMediaId();

    /**
     * Set teaser-media-id.
     *
     * @param int $teaserMediaId
     *
     * @return $this
     */
    public function setTeaserMediaId($teaserMediaId);

    /**
     * Get published.
     *
     * @return \DateTime
     */
    public function getPublished();

    /**
     * Set published.
     *
     * @param \DateTime $published
     *
     * @return $this
     */
    public function setPublished(\DateTime $published = null);

    /**
     * Get published state.
     *
     * @return bool
     */
    public function getPublishedState();

    /**
     * Set published state.
     *
     * @param bool $publishedState
     *
     * @return $this
     */
    public function setPublishedState($publishedState);

    /**
     * Get localization state.
     *
     * @return LocalizationStateViewObject
     */
    public function getLocalizationState();

    /**
     * Set localization state.
     *
     * @param LocalizationStateViewObject $localizationState
     *
     * @return $this
     */
    public function setLocalizationState(LocalizationStateViewObject $localizationState);

    /**
     * @param string $authorId
     *
     * @return $this
     */
    public function setAuthorId($authorId);

    /**
     * @return string
     */
    public function getAuthorId();

    /**
     * @param string $creatorContactId
     *
     * @return $this
     */
    public function setCreatorContactId($creatorContactId);

    /**
     * @return string
     */
    public function getCreatorContactId();

    /**
     * @param string $changerContactId
     *
     * @return $this
     */
    public function setChangerContactId($changerContactId);

    /**
     * @return string
     */
    public function getChangerContactId();
}
