//
// Browser notification
//
// @author Edgard Lorraine Messias
// @website https://github.com/edgardmessias/browsernotification
// 
//

(function (window, $) {
   function GLPIBrowserNotification(options) {

      var self = this;
      var _queue = $('<div />');
      var _queue_audio = $('<div />');
      var _interval = null;
      var _texts = {};

      this.options = $.extend({}, GLPIBrowserNotification.default, options);

      if (!this.options.icon) {
         this.options.icon = this.options.base_url + '/plugins/browsernotification/pics/glpi.png';
      }

      _texts = GLPIBrowserNotification.default.texts['en-us'];

      if (this.options.locale && typeof GLPIBrowserNotification.default.texts[this.options.locale] !== 'undefined') {
         _texts = $.extend({}, _texts, GLPIBrowserNotification.default.texts[this.options.locale]);
      }

      var self = this;

      function currentTimestamp() {
         return new Date().getTime();
      }

      /**
       * Format string with placeholders
       * 
       * Example: var replacements = {"%NAME%":"Mike","%AGE%":"26","%EVENT%":"20"};
       *          var str = 'My Name is %NAME% and my age is %AGE%.';
       * @param {String} str The string
       * @param {Object} replacements Placeholders
       * @returns {String}
       */
      this.formatString = function (str, replacements) {
         str = str.replace(/%(\w+)%/g, function (all, p1) {
            if (typeof replacements[p1] !== 'undefined') {
               return replacements[p1];
            }
            if (p1 === 'user_id') {
               return self.options.user_id;
            }

            return all;
         });

         return str;
      };

      function getLastIds() {
         var last_id_key = 'glpi_browsernotification_last_id_' + self.options.user_id;
         var content_json = localStorage.getItem(last_id_key);

         if (content_json) {
            try {
               return JSON.parse(content_json);
            } catch (e) {
            }
         }

         return {};
      }

      function showNotification(data_type, data, notif_type) {
         if (typeof _texts[data_type] === 'undefined') {
            console.warn('Text not found for "' + data_type + '"');
            return;
         }

         var texts = _texts[data_type];

         var title = self.formatString(texts[notif_type + '_title'], data);
         var body = self.formatString(texts[notif_type + '_body'], data);

         /**
          * Queue to prevent firefox bug
          * Show next notification after 100ms
          * @see http://stackoverflow.com/questions/33073958/multiple-notifications-with-notifications-api-continuously-in-firefox
          */
         _queue.queue(function () {
            var queue = this;

            setTimeout(function () {
               $(queue).dequeue();
            }, 100);

            if (self.options.sound[data_type] !== false) {
               playAudio(self.options.sound[data_type] || self.options.sound.default);
            }

            var notification = new Notification(title, {
               body: body,
               icon: self.options.icon
            });

            if (typeof self.options.urls[data_type] === 'undefined') {
               return;
            }
            if (typeof self.options.urls[data_type][notif_type + '_url'] === 'undefined') {
               return;
            }

            notification.url_item = self.options.base_url + '/' + self.formatString(self.options.urls[data_type][notif_type + '_url'], data);

            notification.onclick = function (event) {
               event.preventDefault(); // prevent the browser from focusing the Notification's tab
               window.open(this.url_item, '_blank');
            };
         });

      }

      function playAudio(sound) {
         if (!sound || !('Audio' in window)) {
            return false;
         }

         //Queue multiple sounds
         _queue_audio.queue(function () {
            var queue = this;

            var audioElement = new Audio();

            audioElement.onended = function () {
               $(queue).dequeue();
            };

            var base_url = self.options.base_url + '/plugins/browsernotification/sound/';

            if (/^custom_/.test(sound)) {
               base_url = self.options.base_url + '/sounds/';
               sound = sound.replace('custom_', '');
            }

            $(audioElement).append($('<source />', {
               src: base_url + sound + '.mp3',
               type: 'audio/mpeg'
            }));
            $(audioElement).append($('<source />', {
               src: base_url + sound + '.ogg',
               type: 'audio/ogg'
            }));
            $(audioElement).append($('<source />', {
               src: base_url + sound + '.wav',
               type: 'audio/wav'
            }));

            //IF not found audio, play next;
            $(audioElement).find('source:last').on('error', function () {
               $(queue).dequeue();
            });

            audioElement.play();
         });
      }

      this.checkNewNotifications = function () {
         if (!this.isSupported()) {
            return false;
         }

         var last_id_key = 'glpi_browsernotification_last_id_' + self.options.user_id;

         var last_ids = getLastIds();

         var ajax = $.getJSON(this.options.base_url + '/plugins/browsernotification/ajax/check.php', last_ids);
         ajax.done(function (data) {

            var new_last_ids = {};

            for (var t in data) {
               if (data[t] === false) {
                  new_last_ids[t] = last_ids[t] || -1;
               } else {
                  new_last_ids[t] = data[t].last_id;
               }
            }

            localStorage.setItem(last_id_key, JSON.stringify(new_last_ids));

            for (var type in data) {
               if (!data.hasOwnProperty(type) || data[type] === false) {
                  continue;
               }

               //Not show on first load
               if (typeof last_ids[type] === 'undefined') {
                  continue;
               }

               var items = data[type].items;
               var count = data[type].count;

               if (count === 0) {
                  continue;
               }

               if (items.length > 0) {
                  items.forEach(function (item) {
                     showNotification(type, item, 'item');
                  });
               } else {
                  showNotification(type, {count: count}, 'count');
               }

            }

         });
      };

      function checkConcurrence() {
         //simple concurrency check

         var lastcheck_key = 'glpi_browsernotification_lastcheck_' + self.options.user_id;
         var lastCheck = localStorage.getItem(lastcheck_key);

         if (!lastCheck) {
            lastCheck = 0;
         }

         //50ms tolerance
         if (lastCheck <= currentTimestamp() - this.options.interval + 50) {
            localStorage.setItem(lastcheck_key, currentTimestamp());
            self.checkNewNotifications();
         }

      }

      function startMonitoring() {
         checkConcurrence.apply(self);
         _interval = setInterval(checkConcurrence.bind(self), self.options.interval);
      }

      function checkPermission() {
         // Let's check whether notification permissions have already been granted
         if (Notification.permission === "granted") {
            startMonitoring();
         }
         // Otherwise, we need to ask the user for permission
         else if (Notification.permission !== 'denied') {
            Notification.requestPermission(function (permission) {
               // If the user accepts, let's create a notification
               if (permission === "granted") {
                  startMonitoring();
               }
            });
         }
      }

      this.start = function () {
         if (!this.isSupported() || _interval) {
            return false;
         }

         checkPermission();
      };

      this.stop = function () {
         if (_interval) {
            clearInterval(_interval);
            _interval = null;
            return true;
         }
         return false;
      };

      this.isSupported = function () {
         return "Notification" in window && "localStorage" in window;
      };

      this.showExample = function (sound) {
         if (!this.isSupported()) {
            alert('Not supported');
            return false;
         }

         var notification = new Notification('Example notification', {
            body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua',
            icon: self.options.icon
         });

         //For disabled state
         if (sound === '0') {
            sound = false;
         }
         playAudio(sound);
      };

   }

   GLPIBrowserNotification.default = {
      user_id: 0,
      base_url: '',
      interval: 10000,
      sound: {
         default: false
      },
      locale: 'en-us',
      urls: {
         new_ticket: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$main",
            count_url: "front/ticket.php?is_deleted=0&sort=15&order=DESC&criteria[0][field]=12&criteria[0][searchtype]=equals&criteria[0][value]=1&start=0"
         },
         assigned_ticket: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$main",
            count_url: "front/ticket.php?is_deleted=0&sort=19&order=DESC&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=%user_id%&start=0"
         },
         assigned_group_ticket: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$main",
            count_url: "front/ticket.php?is_deleted=0&sort=19&order=DESC&criteria[0][field]=8&criteria[0][searchtype]=equals&criteria[0][value]=mygroups&start=0"
         },
         ticket_followup: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$1",
            count_url: "front/ticket.php?is_deleted=0&sort=36&order=DESC&criteria[0][field]=36&criteria[0][searchtype]=morethan&criteria[0][value]=-1HOUR&start=0"
         },
         ticket_validation: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=TicketValidation$1",
            count_url: "front/ticket.php?is_deleted=0&sort=19&order=DESC&criteria[0][field]=52&criteria[0][searchtype]=equals&criteria[0][value]=2&criteria[1][link]=AND&criteria[1][field]=59&criteria[1][searchtype]=equals&criteria[1][value]=%user_id%&start=0"
         },
         ticket_status: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$main",
            count_url: "front/ticket.php?is_deleted=0&sort=19&order=DESC&criteria[0][field]=36&criteria[0][searchtype]=morethan&criteria[0][value]=-1HOUR&start=0"
         },
         ticket_task: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$1",
            count_url: "front/ticket.php?is_deleted=0&sort=97&order=DESC&criteria[0][field]=97&criteria[0][searchtype]=morethan&criteria[0][value]=-1HOUR&start=0"
         },
         ticket_document: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$1",
            count_url: "front/ticket.php?is_deleted=0&sort=19&order=DESC&criteria[0][field]=119&criteria[0][searchtype]=morethan&criteria[0][value]=&gt;0&start=0"
         },
         ticket_scheduled_task: {
            item_url: "front/ticket.form.php?id=%ticket_id%&forcetab=Ticket$1",
            count_url: "front/ticket.php?is_deleted=0&sort=173&order=DESC&criteria[0][field]=173&criteria[0][searchtype]=morethan&criteria[0][value]=-1HOUR&start=0"
         }
      },
      texts: {
         'en-us': {
            new_ticket: {
               item_title: "New ticket #%ticket_id%",
               item_body: "%name%",
               count_title: "New tickets",
               count_body: "You have %count% new tickets"
            },
            assigned_ticket: {
               item_title: "New assignment in ticket (#%ticket_id%)",
               item_body: "You assigned to ticket #%ticket_id%\n%name%",
               count_title: "New assignment in tickets",
               count_body: "You have %count% new tickets assigned"
            },
            assigned_group_ticket: {
               item_title: "New group assignment in ticket (#%ticket_id%)",
               item_body: "Your group assigned to ticket #%ticket_id%\n%name%",
               count_title: "New group assignment in tickets",
               count_body: "Your group have %count% new tickets assigned"
            },
            ticket_followup: {
               item_title: "New followup on ticket #%ticket_id%",
               item_body: "%user% (%type_name%):\n%content%",
               count_title: "New followups",
               count_body: "You have %count% new followups"
            },
            ticket_validation: {
               item_title: "Approval request on ticket #%ticket_id%",
               item_body: "An approval request has been submitted by %user%:\n%comment_submission%",
               count_title: "Approval requests",
               count_body: "You have %count% new approval requests"
            },
            ticket_status: {
               item_title: "Status updated on ticket #%ticket_id%",
               item_body: "Status of #%ticket_id% is changed to\n%status%\nby %user_name%",
               count_title: "Tickets status updated",
               count_body: "You have %count% new tickets status updated"
            },
            ticket_task: {
               item_title: "New task on ticket #%ticket_id%",
               item_body: "New task (%state_text%):\n%content%",
               count_title: "New tasks",
               count_body: "You have %count% new tasks"
            },
            ticket_document: {
               item_title: "New document on ticket #%ticket_id%",
               item_body: "The document \"%filename%\" has added on ticket #%ticket_id%",
               count_title: "New documents",
               count_body: "You have %count% new documents"
            },
            ticket_scheduled_task: {
               item_title: "Task scheduled on ticket #%ticket_id%",
               item_body: "Task scheduled for %datetime_format%:\n%content%",
               count_title: "Scheduled Tasks",
               count_body: "You have %count% scheduled tasks for now"
            }
         }
      }
   };

   window.GLPIBrowserNotification = GLPIBrowserNotification;
})(window, jQuery);