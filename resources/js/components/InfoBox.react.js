/*
 * InfoBox.react.js
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

import React from 'react';
import axios from 'axios';

class InfoBox extends React.Component {
    state = {
        posts: []
    };

    componentDidMount() {
        axios.get(`http://www.reddit.com/r/${this.props.subreddit}.json`)
            .then(res => {
                const posts = res.data.data.children.map(obj => obj.data);
                this.setState({ posts });
            });
    }

    render() {
        return (
            <div>
                <h1>{`/r/${this.props.subreddit}`}</h1>
                <ul>
                    {this.state.posts.map(post =>
                                              <li key={post.id}>{post.title}</li>
                    )}
                </ul>
            </div>
        );
    }
}
export default InfoBox;