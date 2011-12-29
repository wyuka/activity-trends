function [retRed, retBlue, oldbp, elaspedtime] = brim(adjmat, B, red, blue, m)

starttime = time;
oldbp = -1;

while(true)
    tmpmat = B*blue;
    for i = 1:rows(tmpmat)
        [val, pos] = max(tmpmat(i,:));
        red(i,:) = zeros(1,columns(red));
        red(i,pos) = 1;
    end
    tmpmat = B'*red;
    bp = 0;
    for i = 1:rows(tmpmat)
        [val, pos] = max(tmpmat(i,:));
        blue(i,:) = zeros(1,columns(blue));
        blue(i,pos) = 1;
        bp += val;
    end
    bp /= m;
    if bp <= oldbp
        retRed = [];
        retBlue = [];
        for i = 1:columns(red)
            if(rows(find(red(:,i))) > 0 || rows(find(blue(:,i))) > 0)
                retRed = [retRed, red(:, i)];
                retBlue = [retBlue, blue(:, i)];
            end
        end
        elaspedtime = time - starttime;
        break;
    else
        oldbp = bp;
    end
end