import {Injectable} from 'angular2/core';
import {Http, Headers} from 'angular2/http';
import 'rxjs/add/operator/map';
import {UserConfig} from './user-config';
import {Toast, Alert} from 'ionic-angular';
import {TabsPage} from '../pages/tabs/tabs';

/*
 Generated class for the DataService provider.

 See https://angular.io/docs/ts/latest/guide/dependency-injection.html
 for more info on providers and Angular 2 DI.
 */
@Injectable()
export class DataService {
    static get parameters() {
        return [[Http], [UserConfig]]
    }

    constructor(http, config) {
        this.http = http;
        this.data = null;
        this.config = config;
        this.BASE_URL = "http://115.28.71.169:9900/";
    }

    login(email, password, nav) {
        let url = this.BASE_URL + 'auth/login';
        let body = JSON.stringify({'email': email, 'password': password});
        let headers = new Headers({'Content-Type': 'application/json'});
        this.http.post(url, body, {headers: headers})
            .map(res => res.json())
            .subscribe(data => {
                console.log(data.msg);
                if (data.status == 200) {
                    this.showToast('登录成功！', 2000, nav);
                    nav.rootNav.setRoot(TabsPage);
                    // nav.pop();
                    this.config.setAuth(data.data.access_token, data.data.refresh_token);
                    this.profile();
                } else {
                    this.showToast('用户名或密码不正确...', 2000, nav);
                }
            }, error => {
                this.showToast('无法连接到服务器...', 2000, nav);
            });
    }

    feedback(content, nav) {
        this.config.getAccessToken().then(
            (value) => {
                let url = this.BASE_URL + 'user/feedback';
                let body = JSON.stringify({'access_token': value, 'content': content});
                let headers = new Headers({'Content-Type': 'application/json'});
                this.http.post(url, body, {headers: headers})
                    .map(res => res.json())
                    .subscribe(data => {
                        console.log(data.msg);
                        if (data.status == 200) {
                            this.showToast('提交成功~', 2000, nav);
                        } else {
                            this.showToast('提交失败...', 2000, nav);
                        }
                    }, error => {
                        this.showToast('无法连接到服务器...', 2000, nav);
                    });
            }
        );
    }

    register(email, nickname, password, nav) {
        let url = this.BASE_URL + 'auth/register';
        let body = JSON.stringify({'email': email, 'nickname': nickname, 'password': password});
        let headers = new Headers({'Content-Type': 'application/json'});
        return new Promise(resolve => {
            this.http.post(url, body, {headers: headers})
                .map(res => res.json())
                .subscribe(data => {
                    console.log(data.msg);
                    if (data.status == 200) {
                        this.showAlert('注册成功！', '已向您邮箱发送一封激活邮件，点击激活后即可登录。', nav);
                        resolve();
                    } else {
                        this.showToast('注册失败...', 2000, nav);
                    }
                }, error => {
                    this.showToast('无法连接到服务器...', 2000, nav);
                });
        });
    }

    refresh() {
        this.config.getRefreshToken().then(
            (value) => {
                let url = this.BASE_URL + 'auth/refresh/' + value;
                this.http.get(url)
                    .map(res => res.json())
                    .subscribe(data => {
                        console.log(data.msg);
                        if (data.status == 200) {
                            this.config.setAuth(data.data.access_token, data.data.refresh_token);
                        } else {
                            // todo: 登出。
                            this.config.logout();
                        }
                    }, error => {
                        // pass
                    });
            }
        );
    }

    version(nav) {
        let url = this.BASE_URL + 'version';
        return new Promise((resolve, reject) => {
            this.http.get(url)
                .subscribe(data => {
                    if (data._body == this.config.getVersion()) {
                        resolve();
                    } else {
                        reject();
                    }
                }, error => {
                    this.showToast('无法连接到服务器...', 2000, nav);
                });
        });
    }

    profile() {
        this.config.getAccessToken().then(
            (value) => {
                let url = this.BASE_URL + 'user/profile';
                let body = JSON.stringify({'access_token': value});
                let headers = new Headers({'Content-Type': 'application/json'});
                this.http.get(url, body, {headers: headers})
                    .map(res => res.json())
                    .subscribe(data => {
                        console.log(data.msg);
                        if (data.status == 200) {
                            this.config.setUserMail(data.data.email);
                            this.config.setUserId(data.data.id);
                            this.config.setUserNickname(data.data.nickname);
                            this.config.setIsAutoNotify(data.data.is_auto_notify);
                        } else {
                            // never.
                        }
                    }, error => {
                        // pass.
                    });
            }
        );
    }

    resetPassword(email, password, nav) {
        let url = this.BASE_URL + 'auth/reset';
        let body = JSON.stringify({'email': email, 'password': password});
        let headers = new Headers({'Content-Type': 'application/json'});
        return new Promise(resolve => {
            this.http.patch(url, body, {headers: headers})
                .map(res => res.json())
                .subscribe(data => {
                    console.log(data.msg);
                    if (data.status == 200) {
                        resolve();
                    } else {
                        this.showToast('修改失败...', 2000, nav);
                    }
                }, error => {
                    this.showToast('无法连接到服务器...', 2000, nav);
                });
        });
    }

    showToast(msg, dur, nav) {
        let t = Toast.create({
            message: msg,
            duration: dur
        });
        nav.present(t);
    }

    showAlert(title, sub, nav) {
        let t = Alert.create({
            title: title,
            subTitle: sub,
            buttons: ['OK']
        });
        nav.present(t);
    }
}

