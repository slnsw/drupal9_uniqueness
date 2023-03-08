/**
 * @file
 * uniqueness.js - JavaScript for the uniqueness width
 */

((Drupal, drupalSettings, $, once) => {
  let uniqueness;

  Drupal.behaviors.uniqueness = {
    attach: (context) => {
      if (!uniqueness) {
        uniqueness = new Drupal.uniqueness(drupalSettings.uniqueness['URL'], $('.uniqueness-dyn'));
      }

      // Search off title.
      const editTitle = once('uniqueness-search-title', '#edit-title-0-value', context);
      editTitle.forEach((elem) => {
        $(elem).bind("keyup input propertychange", function () {
          const input = this.value;
          if (input.length >= uniqueness.minCharacters) {
            uniqueness.search('title', input);
          } else if (input.length == 0 && !uniqueness.prependResults) {
            uniqueness.clear();
          }
        });
      });

      // Search off tags.
      const editTags = once('uniqueness-search-tag', '#edit-taxonomy-tags-1', context);
      editTags.forEach((elem) => {
        $(elem).blur(function () {
          input = this.value;
          // Some tags set.
          if (input.length > 0) {
            uniqueness.search('tags', input);
          }
        });
      });
    }
  };

  Drupal.uniqueness = function (uri, widget) {
    this.uri = uri;
    this.delay = 500;
    this.widget = widget;
    this.list = $('.item-list ul', widget);
    this.notifier = $('.uniqueness-search-notifier', widget);
    this.widgetCSS = {
      'background-image': "url('" + drupalSettings.basePath + "misc/throbber.gif" + "')",
      'background-position': '100% -18px',
      'background-repeat': 'no-repeat'
    };
    this.searchCache = {};
    this.listCache = {};
    this.prependResults = drupalSettings.uniqueness['prependResults'];
    this.entityId = drupalSettings.uniqueness['entityId'];
    this.entityType = drupalSettings.uniqueness['entityType'];
    this.bundle = drupalSettings.uniqueness['bundle'];
    this.minCharacters = drupalSettings.uniqueness['minCharacters'];
    this.autoOpen = $(widget).closest('details');
  };

  Drupal.uniqueness.prototype.update = function (data) {
    var expand = FALSE;
    uniqueness.notifier.removeClass('uniqueness-dyn-searching').empty();
    uniqueness.widget.css('background-image', '');
    uniqueness = this;
    if (uniqueness.prependResults) {
      if (typeof data === 'undefined' && uniqueness.listCache != {}) {
        data = uniqueness.listCache;
      }
      var items = '';
      $.each(data, function (i, item) {
        // Only use what we haven't seen before.
        var cacheKey = [
          item.entityType,
          item.bundle,
          item.entityId
        ].filter(function (chunk) {
          return (chunk || '').length > 0;
        }).join('--');
        if (typeof uniqueness.listCache[cacheKey] === 'undefined') {
          items += '<li><a href="' + item.href + '" target="_blank">' + item.title + '</a> ' + (item.status == 0 ? '(' + Drupal.t('not published') + ')' : '') + '</li>';
          // Store the new item.
          uniqueness.listCache[cacheKey] = item;
          expand = TRUE;
        }
      });
      // Show list.
      this.list.prepend(items);
    }
    else { // Replace content. //@todo still use caching?
      $(".uniqueness-description", uniqueness.widget.parent()).toggle(data != undefined);
      if (data == undefined) {
        uniqueness.clear();
        if ($('#edit-title')[0].value.length) {
          uniqueness.notifier.html(drupalSettings.uniqueness['noResultsString']);
        }
        return;
      }
      var items = '';
      $.each(data, function (i, item) {
        if (item.more) {
          items += '<li>' + Drupal.t("... and others.") + '</li>';
        }
        else {
          items += '<li><a href="' + item.href + '" target="_blank">' + item.title + '</a> ' + (item.status == 0 ? '(' + Drupal.t('not published') + ')' : '') + '</li>';
        }
      });
      this.list.html(items);
      expand = items.length;
    }
    if (expand && uniqueness.autoOpen) {
      uniqueness.autoOpen.find('summary').click();
      // Only auto open the fieldset once per page load.
      uniqueness.autoOpen = NULL;
    }
  };

  Drupal.uniqueness.prototype.search = function (element, searchString) {
    uniqueness = this;

    // If this string has been searched for before we do nothing.
    if (uniqueness.prependResults && uniqueness.searchCache[searchString]) {
      return;
    }

    if (this.timer) {
      clearTimeout(this.timer);
    }
    this.timer = setTimeout(function () {
      // Inform user we're searching.
      if (uniqueness.notifier.hasClass('uniqueness-dyn-searching') == FALSE) {
        uniqueness.notifier.addClass('uniqueness-dyn-searching').html(drupalSettings.uniqueness['searchingString']);
        uniqueness.widget.css(uniqueness.widgetCSS);
      }
      var query = uniqueness.uri + '?';
      if (uniqueness.entityId != undefined) {
        query += 'entity_id=' + uniqueness.entityId + '&';
      }
      if (uniqueness.entityType != undefined) {
        query += 'entity_type=' + uniqueness.entityType + '&';
      }
      if (uniqueness.bundle != undefined) {
        query += 'bundle=' + uniqueness.bundle + '&';
      }
      $.getJSON(query + element + '=' + searchString, function (data) {
        if (data != undefined && data != 'false') {
          // Found results.
          uniqueness.update(data);
          // Save this string, it found results.
          uniqueness.searchCache[searchString] = searchString;
          var blockSet = TRUE;
        }
        // Nothing new found so show existing results.
        if (blockSet == undefined) {
          uniqueness.update();
        }
      });
    }, uniqueness.delay);
  };

  Drupal.uniqueness.prototype.clear = function () {
    this.list.empty();
  }
})(Drupal, drupalSettings, jQuery, once);
