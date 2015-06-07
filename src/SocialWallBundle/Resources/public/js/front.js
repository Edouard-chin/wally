(function () {
    window.App = {
        Models: {},
        Collections: {},
        Views: {},
        MaxTiles: 16
    }

    App.Models.SocialPost = Backbone.Model.extend({
        defaults: {
            alreadyDisplayed: false
        }
    });

    App.Collections.SocialPosts = Backbone.Collection.extend({
        model: App.Models.SocialPost
    })

    App.Views.SocialPost = Backbone.View.extend({
        initialize: function () {
            this.model.set('alreadyDisplayed', true);
        },

        template: _.template($("#post-template").html()),

        render: function () {
            this.$el.html(this.template(this.model.toJSON()));

            return this;
        }
    })

    App.Views.SocialPosts = Backbone.View.extend({
        initialize: function () {
            this.on('cycle', this.cycle);
            this.collection.on('add', this.addOne, this);
            var stackPosts = this.collection.filter(function (post, key) {
                if (key >= App.MaxTiles) {
                    return;
                }
                return !post.get('alreadyDisplayed');
            })
            _.each(stackPosts, function (socialPost) {
                var socialPost = new App.Views.SocialPost({model: socialPost});
                this.$el.append(socialPost.render().el);
            }, this)
        },

        addOne: function (e) {
            this.render(e);
        },

        cycle: function (e) {
            this.render(e);
        },

        render: function (post) {
            var $tile = this.$el.children(':nth-child('+_.random(1, App.MaxTiles)+')');
            var post = post ? post : this.collection.find(function (post, key) {
                return !post.get('alreadyDisplayed');
            })
            if (!post) {
                return;
            }
            var socialPostView = new App.Views.SocialPost({model: post});
            $tile.fadeOut();
            $tile.html(socialPostView.render().el);
            $tile.fadeIn();

            return this;
        }
    })
})();
