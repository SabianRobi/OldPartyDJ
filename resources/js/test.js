// import Smarquee from "smarquee";
import { throttle } from "lodash";
import SuperMarquee from "sp-supermarquee";

// document.querySelector('#volume').addEventListener("input", throttle(onVolumeChange, 250));

// function onVolumeChange(e) {
//     console.log(e.target.value);
//     // player.setVolume(e.target.value)
// }


// document.querySelectorAll('.marquee').forEach(e => {
//     if(e.dataset.text.length > 30) {
//         // e.style.maxWidth = '30ch';
//         // e.style.whitespace = 'nowrap';
//         // e.style.overflow-y = 'hidden';
//         // e.style.overflow-x = 'hidden';
//     //     new SuperMarquee(e, {
//     //         content: e.dataset.text,
//     //         speed: 'slow',
//     //         spacer: '       '
//     //     });
//     //     console.log('mqruwee');
//     //     const vis = e.querySelector('.supermarquee-container');
//     //     vis.style.visibility = hidden;
//     //     setTimeout(() => {
//     //         console.log('visibel');
//     //         vis.style.visibility = visible;
//     //     }, 1000);
//     // } else {
//     //     e.innerHTML = e.dataset.text;
//     }
// });



// let title = new Smarquee({selector: '#title'});
// title.init(false);
// if(title.needsMarquee) {
//     title.activate();
// }

// let artists = new Smarquee({selector: '#artists'});
// artists.init(false);
// if(artists.needsMarquee) {
//     artists.activate();
// }


document.querySelectorAll('marquee-text').forEach(obj => {
    console.log(obj.dataset.duration);
    if(obj.innerText.length < 30) {
        obj.setAttribute('duration', '0s');
    } else {
        obj.setAttribute('attribute', obj.dataset.duration)
    }
})
