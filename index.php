<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Test</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/backbone.js/1.2.2/backbone-min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/backbone-localstorage.js/1.1.16/backbone.localStorage-min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
		<link rel="stylesheet" href="style.css" />
	</head>
	<body>
		<div id="participant-page">
			<h1>Backbone test</h1>
			<p>List with create, read, update, delete and sort actions</p>
			<div class="listContainer" id="listContainer">
				<ul class="list">
					<li class="listElement">
						Order: <span id="orderAsc">asc</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span id="orderDesc">desc</span>
					</li>
				</ul>
				<ul class="list ui-sortable" id="dataList">
				</ul>
				<ul class="list">
					<li class="listElement listNewItem">
						<form class="createNewForm">
							<div class="textElem">
								<input type="text" id="newItem" placeholder="New item">
							</div>
							<div class="saveBtnElem">
								<input type="button" id="addItem" value="Save">
							</div>
						</form>
					</li>
				</ul>
			</div>
		</div>
		<script>
			$(function() {
				var Participant = Backbone.Model.extend({
					defaults: function() {
						return {
							text: '',
							edited: false
						}					
					},
					initialize: function() {
						
					}
				});
				
				var ParticipantList = Backbone.Collection.extend({
					model: Participant,
					localStorage: new Backbone.LocalStorage("participants-backbone")
				});
				
				//var Participants = new ParticipantList;
						
				var Participants = new ParticipantList([
					new Participant({
						text: "A"
					}),
					new Participant({
						text: "B"
					})
				]);
				
				var ParticipantItem = Backbone.View.extend({
					tagName: "li",
					template: _.template($('#item-template').html()),
					localStorage: new Backbone.LocalStorage("participants-backbone"),
					initialize: function() {
						this.listenTo(this.model, 'destroy', this.remove);
					},
					events: {
						"click .deleteElem": "clear",
						"click .viewElem": "edit",
						"click .saveBtn": "save",
						"click .cancelBtn": "close",
						"drop": "drop"
					},
					render: function() {
						this.$el.html(this.template(this.model.toJSON()));
						return this;
					},
					clear: function() {
						console.log("poista");
						this.model.destroy();
					},
					edit: function() {
						this.model.set({ edited: true });
						this.render();
					},
					close: function() {
						this.model.set({ edited: false });
						this.render();
					},
					save: function() {
						this.model.save({ text: this.$(".textField").val() })
						this.close();
					},
					drop: function(event, index) {
						console.log("Drop triggeröityi");
						this.$el.trigger('update-sort', [this.model, index]);
					},
				});
				
				var AppView = Backbone.View.extend({
					el: $("#listContainer"),
					initialize: function() {
						this.text = this.$("#newItem");
						this.listenTo(Participants, 'add sort change', this.render);
						this.render();
					},
					events: {
						"click #addItem": "addParticipant",
						"click #orderAsc": "sortParticipantsAsc",
						"click #orderDesc": "sortParticipantsDesc",
						"update-sort": "updateSort"
					},
					render: function() {
						$("#dataList").empty();
						Participants.forEach(function(participant) {
							var view = new ParticipantItem({model: participant});
							this.$("#dataList").append(view.render().el);
						});
					},
					addParticipant: function() {
						Participants.create({
							text: this.text.val()
						});
						console.log(Participants);
						this.text.val("");
					},
					sortParticipantsAsc: function() {
						Participants.comparator = function(item) {
							return item.get('text');
						}
						Participants.sort();
						Participants.comparator = function() {}
					},
					sortParticipantsDesc: function() {
						Participants.comparator = function(item1, item2) {
							if (item1.get('text') > item2.get('text')) return -1; // before
							if (item2.get('text') > item1.get('text')) return 1; // after
							return 0; // equal
						};
						Participants.sort();
						Participants.comparator = function() {}
					},
					updateSort: function (event, model, position) {
						console.log("Tallenna järjestys");
						Participants.remove(model);

						Participants.each(function (model, index) {
							var ordinal = index;
							if (index >= position)
								ordinal += 1;
							model.set('ordinal', ordinal);
						});            

						model.set('ordinal', position);
						Participants.add(model, {at: position});

						// to update ordinals on server:
						var ids = Participants.pluck('id');
						console.log('post ids to server: ' + ids.join(', '));

						this.render();
						$('#dataList').sortable('refresh');
					}
				});
				
				var App = new AppView;

				$('#dataList').sortable({
					stop: function(event, ui) {
						ui.item.trigger('drop', ui.item.index());
					}
				}); 
			});
		</script>
		
		<!-- Templates -->
		
		<script type="text/template" id="item-template">
			<div class="listElement">
				<div class="textElem">
					<% if (edited) { %>
						<span>
							<input type="text" value=<%= text %> class="textField" />
							<input type="button" value="Save" class="saveBtn" />
							<input type="button" value="Cancel" class="cancelBtn" />
						</span>
					<% } else { %>
						<span class="viewElem"><%= text %></span>
					<% } %>
				</div>
				<div class="deleteElem">
					[x]
				</div>
			</div>
		</script>
	</body>
</html>